<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature;

use Closure;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Payment\DTOs\UpdatePaymentDTO;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Events\PaymentSucceeded;
use Modules\Payment\Models\Payment;
use Modules\Payment\Tests\Support\InteractsWithPaymentGateways;
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
        Subscription::updated(function (Subscription $subscription) use ($journal) {
            if ($subscription->wasChanged('status') && $subscription->status === SubscriptionStatus::ACTIVE) {
                file_put_contents($journal, "activated:{$subscription->id}\n", FILE_APPEND | LOCK_EX);
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

        foreach ($workers as $worker) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->fail('Could not fork a worker process.');
            }

            if ($pid === 0) {
                $this->runChild($worker);
            }

            $children[] = $pid;
        }

        foreach ($children as $child) {
            pcntl_waitpid($child, $status);
        }

        DB::reconnect();
    }

    private function runChild(Closure $worker): never
    {
        try {
            DB::reconnect();
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
