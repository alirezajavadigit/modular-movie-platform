<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature;

use Closure;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Payment\DTOs\CreatePaymentDTO;
use Modules\Payment\DTOs\UpdatePaymentDTO;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Events\PaymentSucceeded;
use Modules\Payment\Models\Payment;
use Modules\Payment\Tests\Support\InteractsWithPaymentGateways;
use Modules\Subscription\Contracts\SubscriptionServiceInterface;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Subscription;
use Tests\TestCase;
use Throwable;

final class PaymentConcurrencyTest extends TestCase
{
    use DatabaseMigrations;
    use InteractsWithPaymentGateways;

    private string $journal;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql' || !function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires PostgreSQL and the pcntl extension to run real concurrent transactions.');
        }

        $this->setUpPaymentGateways();

        $this->journal = tempnam(sys_get_temp_dir(), 'payment-concurrency-');
        $journal = $this->journal;

        $this->app['events']->listen(PaymentSucceeded::class, function (PaymentSucceeded $event) use ($journal) {
            file_put_contents($journal, "succeeded:{$event->payment->id}\n", FILE_APPEND | LOCK_EX);
        });
        $this->app['events']->listen(PaymentFailed::class, function (PaymentFailed $event) use ($journal) {
            file_put_contents($journal, "failed:{$event->payment->id}\n", FILE_APPEND | LOCK_EX);
        });
        $this->app['events']->listen(MessageLogged::class, function (MessageLogged $log) use ($journal) {
            file_put_contents($journal, 'log:' . $log->level . ':' . json_encode(['message' => $log->message, 'context' => $log->context]) . "\n", FILE_APPEND | LOCK_EX);
        });
        Subscription::updated(function (Subscription $subscription) use ($journal) {
            file_put_contents($journal, "updated:{$subscription->id}:{$subscription->status->value}:{$subscription->payment_id}:{$subscription->starts_at?->toIso8601String()}\n", FILE_APPEND | LOCK_EX);

            if ($subscription->wasChanged('status') && $subscription->status === SubscriptionStatus::ACTIVE) {
                file_put_contents($journal, "activated:{$subscription->id}\n", FILE_APPEND | LOCK_EX);
            }

            if ($subscription->wasChanged('status') && $subscription->status === SubscriptionStatus::CANCELED) {
                file_put_contents($journal, "canceled:{$subscription->id}\n", FILE_APPEND | LOCK_EX);
            }
        });
    }

    protected function tearDown(): void
    {
        if (isset($this->journal) && is_file($this->journal)) {
            unlink($this->journal);
        }

        parent::tearDown();
    }

    public function test_two_simultaneous_callbacks_settle_one_payment_exactly_once(): void
    {
        $payment = $this->paidPayment();
        $authority = $payment->transaction_id;

        $this->runConcurrently([
            fn () => app(PaymentServiceInterface::class)->callback('zarinpal', ['Authority' => $authority, 'Status' => 'OK']),
            fn () => app(PaymentServiceInterface::class)->callback('zarinpal', ['Authority' => $authority, 'Status' => 'OK']),
        ]);

        $this->assertSettledOnce($payment);
    }

    public function test_simultaneous_callback_and_verify_request_settle_one_payment_exactly_once(): void
    {
        $payment = $this->paidPayment();
        $authority = $payment->transaction_id;

        $this->runConcurrently([
            fn () => app(PaymentServiceInterface::class)->callback('zarinpal', ['Authority' => $authority, 'Status' => 'OK']),
            fn () => app(PaymentServiceInterface::class)->verify(new UpdatePaymentDTO(paymentId: $payment->id, status: PaymentStatus::PENDING)),
        ]);

        $this->assertSettledOnce($payment);
    }

    public function test_two_payments_of_one_subscription_succeeding_together_activate_it_once(): void
    {
        [$subscription, $first, $second] = $this->subscriptionWithTwoAttempts();
        $this->zarinPal->markPaid($first->transaction_id);
        $this->zarinPal->markPaid($second->transaction_id);
        $this->holdTransactionsOpen();

        $this->runConcurrently([$this->callbackFor($first), $this->callbackFor($second)]);

        $this->assertNoWorkerErrors();
        $this->assertSame(PaymentStatus::SUCCESS, $first->fresh()->status);
        $this->assertSame(PaymentStatus::SUCCESS, $second->fresh()->status);
        $this->assertSame(1, $this->journalCount("succeeded:{$first->id}"));
        $this->assertSame(1, $this->journalCount("succeeded:{$second->id}"));
        $this->assertSame(1, $this->journalCount("activated:{$subscription->id}"));

        $updates = $this->journalLines("updated:{$subscription->id}:");
        $this->assertCount(1, $updates, 'payment_id, starts_at and ends_at must be written exactly once.');

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $winner = $subscription->payment_id;
        $loser = $winner === $first->id ? $second->id : $first->id;
        $this->assertContains($winner, [$first->id, $second->id]);
        $this->assertSame("updated:{$subscription->id}:active:{$winner}:{$subscription->starts_at->toIso8601String()}", $updates[0]);

        $reconciliation = $this->journalLogs('error', 'reconciliation');
        $this->assertCount(1, $reconciliation, 'The second successful payment must be reported for manual reconciliation.');
        $this->assertSame($loser, $reconciliation[0]['context']['payment_id']);
        $this->assertSame($winner, $reconciliation[0]['context']['active_payment_id']);
        $this->assertSame($subscription->id, $reconciliation[0]['context']['subscription_id']);
    }

    public function test_two_payments_of_one_subscription_failing_together_cancel_it(): void
    {
        [$subscription, $first, $second] = $this->subscriptionWithTwoAttempts();
        $this->zarinPal->markFailed($first->transaction_id);
        $this->zarinPal->markFailed($second->transaction_id);
        $this->holdTransactionsOpen();

        $this->runConcurrently([$this->callbackFor($first), $this->callbackFor($second)]);

        $this->assertNoWorkerErrors();
        $this->assertSame(PaymentStatus::FAILED, $first->fresh()->status);
        $this->assertSame(PaymentStatus::FAILED, $second->fresh()->status);
        $this->assertSame(SubscriptionStatus::CANCELED, $subscription->fresh()->status, 'Concurrent failures must not leave the subscription pending forever.');
        $this->assertSame(1, $this->journalCount("canceled:{$subscription->id}"));
    }

    public function test_one_payment_succeeding_while_another_fails_leaves_the_subscription_active(): void
    {
        [$subscription, $first, $second] = $this->subscriptionWithTwoAttempts();
        $this->zarinPal->markPaid($first->transaction_id);
        $this->zarinPal->markFailed($second->transaction_id);
        $this->holdTransactionsOpen();

        $this->runConcurrently([$this->callbackFor($first), $this->callbackFor($second)]);

        $this->assertNoWorkerErrors();
        $this->assertSame(PaymentStatus::SUCCESS, $first->fresh()->status);
        $this->assertSame(PaymentStatus::FAILED, $second->fresh()->status);
        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertSame($first->id, $subscription->payment_id);
        $this->assertSame(1, $this->journalCount("activated:{$subscription->id}"));
        $this->assertSame(0, $this->journalCount("canceled:{$subscription->id}"));
    }

    public function test_repeated_administrative_activations_activate_the_subscription_once(): void
    {
        [$subscription] = $this->subscriptionWithTwoAttempts();
        $this->holdTransactionsOpen();

        $this->runConcurrently([$this->adminActivation($subscription->id), $this->adminActivation($subscription->id)]);

        $this->assertNoWorkerErrors();
        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->fresh()->status);
        $this->assertSame(1, $this->journalCount("activated:{$subscription->id}"));
        $this->assertCount(1, $this->journalLines("updated:{$subscription->id}:"));
        $this->assertSame(1, $this->journalCount('rejected:' . LogicException::class));
    }

    public function test_repeated_cancellations_cancel_the_subscription_once(): void
    {
        [$subscription] = $this->subscriptionWithTwoAttempts();
        $this->holdTransactionsOpen();

        $this->runConcurrently([$this->cancellation($subscription->id), $this->cancellation($subscription->id)]);

        $this->assertNoWorkerErrors();
        $this->assertSame(SubscriptionStatus::CANCELED, $subscription->fresh()->status);
        $this->assertSame(1, $this->journalCount("canceled:{$subscription->id}"));
        $this->assertSame(1, $this->journalCount('rejected:' . LogicException::class));
    }

    public function test_administrative_activation_racing_a_payment_settlement_activates_once(): void
    {
        [$subscription, $first] = $this->subscriptionWithTwoAttempts();
        $this->zarinPal->markPaid($first->transaction_id);
        $this->holdTransactionsOpen();

        $this->runConcurrently([$this->callbackFor($first), $this->adminActivation($subscription->id)]);

        $this->assertNoWorkerErrors();
        $this->assertSame(PaymentStatus::SUCCESS, $first->fresh()->status);
        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->fresh()->status);
        $this->assertSame(1, $this->journalCount("activated:{$subscription->id}"));
        $this->assertCount(1, $this->journalLines("updated:{$subscription->id}:"));
    }

    public function test_cancellation_racing_a_payment_settlement_leaves_a_consistent_subscription(): void
    {
        [$subscription, $first] = $this->subscriptionWithTwoAttempts();
        $this->zarinPal->markPaid($first->transaction_id);
        $this->holdTransactionsOpen();

        $this->runConcurrently([$this->callbackFor($first), $this->cancellation($subscription->id)]);

        $this->assertNoWorkerErrors();
        $this->assertSame(PaymentStatus::SUCCESS, $first->fresh()->status);
        $this->assertSame(1, $this->journalCount("activated:{$subscription->id}"));

        $subscription->refresh();
        $updates = $this->journalLines("updated:{$subscription->id}:");
        $this->assertCount(2, $updates);
        $this->assertStringStartsWith("updated:{$subscription->id}:{$subscription->status->value}:", end($updates), 'The last committed transition must be the final state.');
        $this->assertNotNull($subscription->starts_at);
        $this->assertSame($first->id, $subscription->payment_id);
    }

    private function subscriptionWithTwoAttempts(): array
    {
        $first = $this->subscribe($this->customer(), $this->plan());
        $subscription = $first->payable;

        app(PaymentServiceInterface::class)->request(new CreatePaymentDTO(payable: $subscription, userId: $subscription->user_id, driver: 'zarinpal'));
        $second = Payment::query()->latest('id')->firstOrFail();

        $this->zarinPal->delayVerify(200000);

        return [$subscription, $first, $second];
    }

    private function holdTransactionsOpen(int $microseconds = 300000): void
    {
        $this->app['events']->listen(PaymentSucceeded::class, fn () => usleep($microseconds));
        $this->app['events']->listen(PaymentFailed::class, fn () => usleep($microseconds));
        Subscription::updated(fn () => usleep($microseconds));
    }

    private function callbackFor(Payment $payment): Closure
    {
        $authority = $payment->transaction_id;

        return fn () => app(PaymentServiceInterface::class)->callback('zarinpal', ['Authority' => $authority, 'Status' => 'OK']);
    }

    private function adminActivation(int $subscriptionId): Closure
    {
        return $this->rejectable(fn () => app(SubscriptionServiceInterface::class)->activate(Subscription::query()->findOrFail($subscriptionId)));
    }

    private function cancellation(int $subscriptionId): Closure
    {
        return $this->rejectable(fn () => app(SubscriptionServiceInterface::class)->cancel(Subscription::query()->findOrFail($subscriptionId)));
    }

    private function rejectable(Closure $worker): Closure
    {
        $journal = $this->journal;

        return function () use ($worker, $journal) {
            try {
                $worker();
            } catch (LogicException $e) {
                file_put_contents($journal, 'rejected:' . LogicException::class . "\n", FILE_APPEND | LOCK_EX);
            }
        };
    }

    private function journalLines(string $prefix): array
    {
        $lines = array_filter(explode("\n", (string) file_get_contents($this->journal)));

        return array_values(array_filter($lines, fn (string $line) => str_starts_with($line, $prefix)));
    }

    private function journalCount(string $line): int
    {
        return count(array_filter($this->journalLines($line), fn (string $candidate) => $candidate === $line));
    }

    private function journalLogs(string $level, string $needle): array
    {
        $logs = array_map(fn (string $line) => json_decode(substr($line, strlen("log:{$level}:")), true), $this->journalLines("log:{$level}:"));

        return array_values(array_filter($logs, fn (array $log) => str_contains($log['message'], $needle)));
    }

    private function assertNoWorkerErrors(): void
    {
        $this->assertSame([], $this->journalLines('error:'));
    }

    private function paidPayment(): Payment
    {
        $payment = $this->subscribe($this->customer(), $this->plan());
        $this->payAtGateway($payment);
        $this->zarinPal->delayVerify(400000);

        return $payment;
    }

    private function runConcurrently(array $workers): void
    {
        DB::disconnect();

        $children = [];
        $startAt = microtime(true) + 0.5;

        foreach ($workers as $worker) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->fail('Could not fork a worker process.');
            }

            if ($pid === 0) {
                $this->runChild($worker, $startAt);
            }

            $children[] = $pid;
        }

        foreach ($children as $child) {
            pcntl_waitpid($child, $status);
        }

        DB::reconnect();
    }

    private function runChild(Closure $worker, float $startAt): never
    {
        try {
            DB::reconnect();

            while (microtime(true) < $startAt) {
                usleep(1000);
            }

            $worker();
        } catch (Throwable $e) {
            file_put_contents($this->journal, 'error:' . get_class($e) . ':' . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        }

        posix_kill(getmypid(), SIGKILL);

        exit(0);
    }

    private function assertSettledOnce(Payment $payment): void
    {
        $lines = array_filter(explode("\n", (string) file_get_contents($this->journal)));
        $errors = array_values(array_filter($lines, fn (string $line) => str_starts_with($line, 'error:')));

        $this->assertSame([], $errors);
        $this->assertSame(PaymentStatus::SUCCESS, $payment->fresh()->status);
        $this->assertSame(SubscriptionStatus::ACTIVE, $payment->fresh()->payable->status);
        $this->assertCount(1, array_keys($lines, "activated:{$payment->payable_id}", true), 'The subscription must be activated exactly once.');
        $this->assertCount(1, array_keys($lines, "succeeded:{$payment->id}", true), 'PaymentSucceeded must be dispatched exactly once.');
    }
}
