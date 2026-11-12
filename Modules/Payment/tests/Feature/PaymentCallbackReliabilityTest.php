<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Payment\DTOs\CreatePaymentDTO;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Tests\Support\InteractsWithPaymentGateways;
use Modules\Subscription\Enums\SubscriptionStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PaymentCallbackReliabilityTest extends TestCase
{
    use InteractsWithPaymentGateways;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPaymentGateways();
    }

    public static function temporaryFailures(): array
    {
        $cases = [];

        foreach (['zarinpal' => 50000, 'stripe' => 19.99, 'paypal' => 19.99, 'zibal' => 50000] as $driver => $price) {
            foreach (['timeout', 429, 500, 502, 503, 504] as $failure) {
                $cases["{$driver} {$failure}"] = [$driver, $price, $failure];
            }
        }

        return $cases;
    }

    #[DataProvider('temporaryFailures')]
    public function test_temporary_gateway_failure_keeps_payment_pending_and_retry_settles_it_once(string $driver, float $price, int|string $failure): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan($price), $driver);
        $reference = $payment->transaction_id;
        $this->payAtGateway($payment);
        $paymentsBefore = Payment::count();

        $this->failGateway($driver, $failure);
        $this->successCallback($payment)->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame($reference, $payment->transaction_id);
        $this->assertSame(SubscriptionStatus::PENDING, $payment->payable->status);
        $this->assertSame(0, $this->succeededEventsFor($payment));
        $this->assertSame(0, $this->failedEventsFor($payment));

        $this->failGateway($driver, null);
        $this->successCallback($payment)->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertSame(SubscriptionStatus::ACTIVE, $payment->payable->status);
        $this->assertSame(1, $this->succeededEventsFor($payment));
        $this->assertSame(1, $this->activationsOf($payment->payable_id));
        $this->assertSame($paymentsBefore, Payment::count());
    }

    public function test_temporary_failure_on_the_verify_endpoint_is_retryable(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(19.99), 'paypal');
        $this->payAtGateway($payment);

        $this->failGateway('paypal', 503);
        $this->verifyAs($owner, $payment)->assertStatus(409);
        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);

        $this->failGateway('paypal', null);
        $this->verifyAs($owner, $payment)->assertOk();

        $this->assertSame(PaymentStatus::SUCCESS, $payment->fresh()->status);
        $this->assertSame(1, $this->succeededEventsFor($payment));
    }

    public function test_zarinpal_definitive_rejection_fails_the_payment(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan());
        $this->zarinPal->markFailed($payment->transaction_id);

        $this->successCallback($payment)->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::FAILED, $payment->status);
        $this->assertSame(SubscriptionStatus::CANCELED, $payment->payable->status);
        $this->assertSame(1, $this->failedEventsFor($payment));
    }

    public function test_stripe_expired_session_fails_the_payment(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(19.99), 'stripe');
        $this->http->expireStripe($payment->transaction_id);

        $this->successCallback($payment)->assertOk();

        $this->assertSame(PaymentStatus::FAILED, $payment->fresh()->status);
        $this->assertSame(1, $this->failedEventsFor($payment));
    }

    public function test_paypal_declined_instrument_waits_for_the_payer_instead_of_failing(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(19.99), 'paypal');
        $this->http->approvePayPal($payment->transaction_id, captureIssue: 'INSTRUMENT_DECLINED');

        $this->successCallback($payment)->assertOk();

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertSame(SubscriptionStatus::PENDING, $payment->payable->fresh()->status);
        $this->assertSame(0, $this->failedEventsFor($payment));
    }

    public function test_paypal_completed_order_with_declined_capture_fails_the_payment(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(19.99), 'paypal');
        $this->http->approvePayPal($payment->transaction_id, captureStatus: 'DECLINED');

        $this->successCallback($payment)->assertOk();

        $this->assertSame(PaymentStatus::FAILED, $payment->fresh()->status);
    }

    public function test_paypal_pending_capture_keeps_the_payment_pending(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(19.99), 'paypal');
        $this->http->approvePayPal($payment->transaction_id, captureStatus: 'PENDING');

        $this->successCallback($payment)->assertOk();

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertSame(0, $this->succeededEventsFor($payment));
    }

    public function test_paypal_already_captured_order_is_settled_from_the_order_details(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(19.99), 'paypal');
        $this->http->approvePayPal($payment->transaction_id);
        $this->http->paypalOrders[$payment->transaction_id]['status'] = 'COMPLETED';

        $this->successCallback($payment)->assertOk();

        $this->assertSame(PaymentStatus::SUCCESS, $payment->fresh()->status);
        $this->assertSame(1, $this->succeededEventsFor($payment));
    }

    public function test_zibal_invalid_track_keeps_the_payment_unresolved(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(), 'zibal');
        $this->http->forceZibalResult($payment->transaction_id, 203);

        $this->successCallback($payment)->assertOk();

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertSame(0, $this->failedEventsFor($payment));
    }

    public function test_zibal_already_verified_track_settles_the_payment(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(), 'zibal');
        $this->http->payZibal($payment->transaction_id);
        $this->http->zibalTracks[$payment->transaction_id]['verified'] = true;

        $this->successCallback($payment)->assertOk();

        $this->assertSame(PaymentStatus::SUCCESS, $payment->fresh()->status);
    }

    public static function forgedCancellations(): array
    {
        return [
            'zarinpal' => ['zarinpal', 50000],
            'paypal'   => ['paypal', 19.99],
            'zibal'    => ['zibal', 50000],
        ];
    }

    #[DataProvider('forgedCancellations')]
    public function test_unverified_cancellation_callback_does_not_cancel_an_open_payment(string $driver, float $price): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan($price), $driver);
        $reference = $payment->transaction_id;

        $this->cancelCallback($driver, $reference)->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame($reference, $payment->transaction_id);
        $this->assertSame(SubscriptionStatus::PENDING, $payment->payable->status);

        $this->payAtGateway($payment);
        $this->successCallback($payment)->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertSame(SubscriptionStatus::ACTIVE, $payment->payable->status);
        $this->assertSame(1, $this->succeededEventsFor($payment));
    }

    public function test_cancellation_callback_for_a_paid_payment_settles_it_instead_of_cancelling(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan());
        $this->payAtGateway($payment);

        $this->cancelCallback('zarinpal', $payment->transaction_id)->assertOk();

        $this->assertSame(PaymentStatus::SUCCESS, $payment->fresh()->status);
        $this->assertSame(1, $this->succeededEventsFor($payment));
    }

    public function test_gateway_confirmed_cancellation_is_preserved(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan());
        $this->zarinPal->markFailed($payment->transaction_id);

        $this->cancelCallback('zarinpal', $payment->transaction_id)->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::CANCELED, $payment->status);
        $this->assertSame(SubscriptionStatus::PENDING, $payment->payable->status);
    }

    public function test_cancellation_callback_with_another_payments_authority_only_touches_that_payment(): void
    {
        $victim = $this->customer();
        $attacker = $this->customer();
        $victimPayment = $this->subscribe($victim, $this->plan());
        $attackerPayment = $this->subscribe($attacker, $this->plan());

        $this->cancelCallback('zarinpal', $victimPayment->transaction_id)->assertOk();

        $this->assertSame(PaymentStatus::PENDING, $victimPayment->fresh()->status);
        $this->assertSame(PaymentStatus::PENDING, $attackerPayment->fresh()->status);
        $this->assertSame($attackerPayment->transaction_id, $attackerPayment->fresh()->transaction_id);
    }

    public function test_callback_through_another_driver_is_rejected_without_changes(): void
    {
        $owner = $this->customer();
        $stripePayment = $this->subscribe($owner, $this->plan(19.99), 'stripe');
        $this->payAtGateway($stripePayment);

        $this->getJson("/api/v1/payments/callback/paypal?token={$stripePayment->transaction_id}")->assertNotFound();
        $this->getJson("/api/v1/payments/callback/zibal?trackId={$stripePayment->transaction_id}&success=0")->assertNotFound();

        $this->assertSame(PaymentStatus::PENDING, $stripePayment->fresh()->status);
    }

    public function test_callback_for_an_unknown_reference_is_rejected(): void
    {
        $this->getJson('/api/v1/payments/callback/zarinpal?Authority=A00000000000000000000000000000000999&Status=NOK')
            ->assertNotFound();
    }

    public function test_repeated_cancellation_callbacks_are_idempotent(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan());
        $this->zarinPal->markFailed($payment->transaction_id);

        $this->cancelCallback('zarinpal', $payment->transaction_id)->assertOk();
        $this->cancelCallback('zarinpal', $payment->transaction_id)->assertOk();
        $this->cancelCallback('zarinpal', $payment->transaction_id)->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::CANCELED, $payment->status);
        $this->assertSame(0, $this->failedEventsFor($payment));
        $this->assertSame(0, $this->succeededEventsFor($payment));
        $this->assertSame(1, $this->zarinPal->verifyCallsFor($payment->transaction_id));
    }

    public function test_old_callback_does_not_affect_a_newer_subscription_attempt(): void
    {
        $owner = $this->customer();
        $plan = $this->plan();
        $abandoned = $this->subscribe($owner, $plan);
        $newer = $this->subscribe($owner, $plan);
        $this->payAtGateway($newer);
        $this->successCallback($newer)->assertOk();
        $this->zarinPal->markFailed($abandoned->transaction_id);

        $this->cancelCallback('zarinpal', $abandoned->transaction_id)->assertOk();
        $this->successCallback($abandoned)->assertOk();

        $newer->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $newer->status);
        $this->assertSame(SubscriptionStatus::ACTIVE, $newer->payable->status);
        $this->assertSame(1, $this->succeededEventsFor($newer));
    }

    public function test_old_payment_failure_does_not_cancel_a_subscription_with_a_newer_pending_attempt(): void
    {
        $owner = $this->customer();
        $oldAttempt = $this->subscribe($owner, $this->plan());
        $subscription = $oldAttempt->payable;

        app(PaymentServiceInterface::class)->request(new CreatePaymentDTO(payable: $subscription, userId: $owner->id, driver: 'zarinpal'));
        $newAttempt = Payment::query()->latest('id')->firstOrFail();
        $this->assertSame($subscription->id, $newAttempt->payable_id);

        $this->zarinPal->markFailed($oldAttempt->transaction_id);
        $this->successCallback($oldAttempt)->assertOk();

        $this->assertSame(PaymentStatus::FAILED, $oldAttempt->fresh()->status);
        $this->assertSame(SubscriptionStatus::PENDING, $subscription->fresh()->status);

        $this->payAtGateway($newAttempt);
        $this->successCallback($newAttempt)->assertOk();

        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->fresh()->status);
        $this->assertSame($newAttempt->id, $subscription->fresh()->payment_id);
    }

    public function test_repeated_success_callbacks_fire_one_event_and_one_activation(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan());
        $this->payAtGateway($payment);

        $this->successCallback($payment)->assertOk();
        $this->successCallback($payment)->assertOk();
        $this->verifyAs($owner, $payment);

        $this->assertSame(PaymentStatus::SUCCESS, $payment->fresh()->status);
        $this->assertSame(1, $this->succeededEventsFor($payment));
        $this->assertSame(1, $this->activationsOf($payment->payable_id));
    }

    public function test_interleaved_callbacks_settle_the_payment_once(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan());
        $this->payAtGateway($payment);
        $authority = $payment->transaction_id;
        $service = app(PaymentServiceInterface::class);

        $this->zarinPal->beforeNextVerify(function () use ($service, $authority) {
            $service->callback('zarinpal', ['Authority' => $authority, 'Status' => 'OK']);
        });

        $result = $service->callback('zarinpal', ['Authority' => $authority, 'Status' => 'OK']);

        $this->assertSame(PaymentStatus::SUCCESS, $result->status);
        $this->assertSame(1, $this->succeededEventsFor($payment));
        $this->assertSame(1, $this->activationsOf($payment->payable_id));
    }

    public function test_interleaved_callback_and_verify_request_settle_the_payment_once(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan());
        $this->payAtGateway($payment);
        $authority = $payment->transaction_id;

        $this->zarinPal->beforeNextVerify(function () use ($authority) {
            app(PaymentServiceInterface::class)->callback('zarinpal', ['Authority' => $authority, 'Status' => 'OK']);
        });

        $this->verifyAs($owner, $payment)->assertOk();

        $this->assertSame(PaymentStatus::SUCCESS, $payment->fresh()->status);
        $this->assertSame(1, $this->succeededEventsFor($payment));
        $this->assertSame(1, $this->activationsOf($payment->payable_id));
    }

    public function test_interleaved_cancel_and_success_callbacks_end_in_success_with_one_event(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan());
        $this->payAtGateway($payment);
        $authority = $payment->transaction_id;
        $service = app(PaymentServiceInterface::class);

        $this->zarinPal->beforeNextVerify(function () use ($service, $authority) {
            $service->callback('zarinpal', ['Authority' => $authority, 'Status' => 'OK']);
        });

        $service->callback('zarinpal', ['Authority' => $authority, 'Status' => 'NOK']);

        $this->assertSame(PaymentStatus::SUCCESS, $payment->fresh()->status);
        $this->assertSame(1, $this->succeededEventsFor($payment));
        $this->assertSame(0, $this->failedEventsFor($payment));
    }
}
