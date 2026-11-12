<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Tests\Support\InteractsWithPaymentGateways;
use Modules\Subscription\Enums\SubscriptionStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PaymentGatewayClassificationTest extends TestCase
{
    use InteractsWithPaymentGateways;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPaymentGateways();
    }

    public function test_zibal_invalid_track_does_not_fail_the_payment_or_cancel_the_subscription(): void
    {
        $payment = $this->zibalPayment();
        $this->http->forceZibalResult($payment->transaction_id, 203);

        $this->successCallback($payment)->assertOk()->assertJsonPath('data.status', 'pending');

        $this->assertUnresolved($payment);
    }

    public function test_zibal_invalid_track_on_a_cancellation_callback_does_not_cancel_anything(): void
    {
        $payment = $this->zibalPayment();
        $this->http->forceZibalResult($payment->transaction_id, 203);

        $this->cancelCallback('zibal', $payment->transaction_id)->assertOk()->assertJsonPath('data.status', 'pending');

        $this->assertUnresolved($payment);
    }

    public function test_zibal_invalid_track_is_logged_for_review_with_sanitized_context(): void
    {
        $payment = $this->zibalPayment();
        $this->http->forceZibalResult($payment->transaction_id, 203);

        $this->successCallback($payment)->assertOk();

        $logs = $this->logsFor($payment, 'error');
        $this->assertCount(1, $logs);
        $this->assertSame('zibal', $logs[0]['context']['driver']);
        $this->assertSame('review', $logs[0]['context']['reason']);
        $this->assertSame(203, $logs[0]['context']['gateway_result']);
        $this->assertLogsAreSanitized();
    }

    public function test_zibal_invalid_track_on_the_verify_endpoint_returns_a_retryable_conflict(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(), 'zibal');
        $this->http->forceZibalResult($payment->transaction_id, 203);

        $this->verifyAs($owner, $payment)->assertStatus(409)->assertJsonPath('success', false);

        $this->assertUnresolved($payment);
    }

    public function test_zibal_payment_left_unresolved_by_an_invalid_track_can_still_be_settled_later(): void
    {
        $payment = $this->zibalPayment();
        $this->http->forceZibalResult($payment->transaction_id, 203);
        $this->successCallback($payment)->assertOk();

        unset($this->http->zibalTracks[$payment->transaction_id]['forced_result']);
        $this->http->payZibal($payment->transaction_id);
        $this->successCallback($payment)->assertOk()->assertJsonPath('data.status', 'success');

        $this->assertSame(SubscriptionStatus::ACTIVE, $payment->payable->fresh()->status);
        $this->assertSame(1, $this->succeededEventsFor($payment));
    }

    public function test_zibal_merchant_configuration_errors_are_logged_for_review(): void
    {
        $payment = $this->zibalPayment();
        $this->http->forceZibalResult($payment->transaction_id, 103);

        $this->successCallback($payment)->assertOk();

        $this->assertUnresolved($payment);
        $this->assertCount(1, $this->logsFor($payment, 'error'));
    }

    public function test_zibal_unpaid_order_stays_pending_without_an_error_log(): void
    {
        $payment = $this->zibalPayment();

        $this->successCallback($payment)->assertOk();

        $this->assertUnresolved($payment);
        $this->assertSame([], $this->logsFor($payment, 'error'));
    }

    public static function payerActionIssues(): array
    {
        return [
            'INSTRUMENT_DECLINED'                    => ['INSTRUMENT_DECLINED'],
            'TRANSACTION_REFUSED'                    => ['TRANSACTION_REFUSED'],
            'MAX_NUMBER_OF_PAYMENT_ATTEMPTS_EXCEEDED' => ['MAX_NUMBER_OF_PAYMENT_ATTEMPTS_EXCEEDED'],
            'CARD_EXPIRED'                           => ['CARD_EXPIRED'],
            'PAYER_ACTION_REQUIRED'                  => ['PAYER_ACTION_REQUIRED'],
        ];
    }

    #[DataProvider('payerActionIssues')]
    public function test_paypal_issue_requiring_payer_action_keeps_the_payment_pending(string $issue): void
    {
        $payment = $this->paypalPayment();
        $this->http->approvePayPal($payment->transaction_id, captureIssue: $issue);

        $this->successCallback($payment)->assertOk()->assertJsonPath('data.status', 'pending');

        $this->assertUnresolved($payment);
        $this->assertSame(1, $this->http->callsTo("{$payment->transaction_id}/capture"));
        $this->assertSame([], $this->logsFor($payment, 'error'));
        $logs = $this->logsFor($payment);
        $this->assertCount(1, $logs);
        $this->assertSame('payer_action', $logs[0]['context']['reason']);
        $this->assertSame([$issue], $logs[0]['context']['issues']);
        $this->assertLogsAreSanitized();
    }

    #[DataProvider('payerActionIssues')]
    public function test_paypal_payment_waiting_for_the_payer_settles_after_the_payer_fixes_it(string $issue): void
    {
        $payment = $this->paypalPayment();
        $this->http->approvePayPal($payment->transaction_id, captureIssue: $issue);
        $this->successCallback($payment)->assertOk();

        $this->http->approvePayPal($payment->transaction_id);
        $this->successCallback($payment)->assertOk()->assertJsonPath('data.status', 'success');

        $this->assertSame(SubscriptionStatus::ACTIVE, $payment->payable->fresh()->status);
        $this->assertSame(1, $this->succeededEventsFor($payment));
    }

    public function test_paypal_unapproved_order_keeps_the_payment_pending(): void
    {
        $payment = $this->paypalPayment();

        $this->successCallback($payment)->assertOk()->assertJsonPath('data.status', 'pending');

        $this->assertUnresolved($payment);
        $this->assertSame('payer_action', $this->logsFor($payment)[0]['context']['reason']);
    }

    public function test_paypal_verify_endpoint_tells_the_client_that_the_payer_must_act(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(19.99), 'paypal');
        $this->http->approvePayPal($payment->transaction_id, captureIssue: 'INSTRUMENT_DECLINED');

        $this->verifyAs($owner, $payment)
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('payment::messages.payer_action_required'));

        $this->assertUnresolved($payment);
    }

    public function test_paypal_voided_order_is_a_definitive_failure(): void
    {
        $payment = $this->paypalPayment();
        $this->http->voidPayPal($payment->transaction_id);

        $this->successCallback($payment)->assertOk()->assertJsonPath('data.status', 'failed');

        $this->assertSame(PaymentStatus::FAILED, $payment->fresh()->status);
        $this->assertSame(SubscriptionStatus::CANCELED, $payment->payable->fresh()->status);
        $this->assertSame(1, $this->failedEventsFor($payment));
    }

    public function test_paypal_voided_order_on_a_cancellation_callback_is_recorded_as_canceled(): void
    {
        $payment = $this->paypalPayment();
        $this->http->voidPayPal($payment->transaction_id);

        $this->cancelCallback('paypal', $payment->transaction_id)->assertOk()->assertJsonPath('data.status', 'canceled');

        $this->assertSame(PaymentStatus::CANCELED, $payment->fresh()->status);
    }

    public function test_paypal_missing_order_is_not_treated_as_a_failed_payment(): void
    {
        $payment = $this->paypalPayment();
        $this->http->forgetPayPal($payment->transaction_id);

        $this->successCallback($payment)->assertOk()->assertJsonPath('data.status', 'pending');

        $this->assertUnresolved($payment);
        $logs = $this->logsFor($payment, 'error');
        $this->assertCount(1, $logs);
        $this->assertSame(404, $logs[0]['context']['http_status']);
    }

    public function test_paypal_unknown_business_error_keeps_the_payment_pending_and_is_logged_for_review(): void
    {
        $payment = $this->paypalPayment();
        $this->http->approvePayPal($payment->transaction_id, captureIssue: 'A_FUTURE_ISSUE_CODE');

        $this->successCallback($payment)->assertOk()->assertJsonPath('data.status', 'pending');

        $this->assertUnresolved($payment);
        $logs = $this->logsFor($payment, 'error');
        $this->assertCount(1, $logs);
        $this->assertSame('review', $logs[0]['context']['reason']);
        $this->assertSame(['A_FUTURE_ISSUE_CODE'], $logs[0]['context']['issues']);
        $this->assertSame('APPROVED', $logs[0]['context']['order_status']);
        $this->assertSame('abc123def456', $logs[0]['context']['debug_id']);
        $this->assertLogsAreSanitized();
    }

    public function test_paypal_business_error_with_an_unsafe_issue_value_is_sanitized_in_logs(): void
    {
        $payment = $this->paypalPayment();
        $this->http->approvePayPal($payment->transaction_id, captureIssue: "BAD\nISSUE <script>paypal-secret</script>");

        $this->successCallback($payment)->assertOk();

        $this->assertUnresolved($payment);
        $issue = $this->logsFor($payment, 'error')[0]['context']['issues'][0];
        $this->assertMatchesRegularExpression('/^[A-Z0-9_]{1,64}$/', $issue);
        $this->assertLogsAreSanitized();
    }

    public static function integrationFailures(): array
    {
        return [
            'token 401'   => ['POST', '/v1/oauth2/token', 401],
            'token 403'   => ['POST', '/v1/oauth2/token', 403],
            'capture 401' => ['POST', '/capture', 401],
            'capture 403' => ['POST', '/capture', 403],
            'capture 400' => ['POST', '/capture', 400],
        ];
    }

    #[DataProvider('integrationFailures')]
    public function test_paypal_authentication_and_integration_errors_keep_the_payment_pending(string $method, string $route, int $status): void
    {
        $payment = $this->paypalPayment();
        $this->http->approvePayPal($payment->transaction_id);
        $this->http->failRoute($method, $route, $status);

        $this->successCallback($payment)->assertOk()->assertJsonPath('data.status', 'pending');

        $this->assertUnresolved($payment);
        $logs = $this->logsFor($payment, 'error');
        $this->assertCount(1, $logs);
        $this->assertSame('review', $logs[0]['context']['reason']);
        $this->assertLogsAreSanitized();
    }

    public static function temporaryOrderLookupFailures(): array
    {
        return ['timeout' => ['timeout'], '503' => [503], '429' => [429]];
    }

    #[DataProvider('temporaryOrderLookupFailures')]
    public function test_paypal_order_lookup_failure_after_a_business_error_keeps_the_payment_pending(int|string $failure): void
    {
        $payment = $this->paypalPayment();
        $this->http->approvePayPal($payment->transaction_id, captureIssue: 'INSTRUMENT_DECLINED');
        $this->http->failRoute('GET', "orders/{$payment->transaction_id}", $failure);

        $this->successCallback($payment)->assertOk()->assertJsonPath('data.status', 'pending');

        $this->assertUnresolved($payment);
        $this->assertSame('temporary', $this->logsFor($payment, 'warning')[0]['context']['reason']);
    }

    public function test_paypal_server_error_is_logged_as_temporary(): void
    {
        $payment = $this->paypalPayment();
        $this->http->approvePayPal($payment->transaction_id);
        $this->http->failRoute('POST', '/capture', 503);

        $this->successCallback($payment)->assertOk();

        $this->assertUnresolved($payment);
        $this->assertSame([], $this->logsFor($payment, 'error'));
        $this->assertSame('temporary', $this->logsFor($payment, 'warning')[0]['context']['reason']);
    }

    public function test_paypal_already_captured_order_with_a_declined_capture_fails_the_payment(): void
    {
        $payment = $this->paypalPayment();
        $this->http->approvePayPal($payment->transaction_id, captureStatus: 'DECLINED');
        $this->http->paypalOrders[$payment->transaction_id]['status'] = 'COMPLETED';

        $this->successCallback($payment)->assertOk()->assertJsonPath('data.status', 'failed');

        $this->assertSame(1, $this->failedEventsFor($payment));
    }

    private function zibalPayment(): Payment
    {
        return $this->subscribe($this->customer(), $this->plan(), 'zibal');
    }

    private function paypalPayment(): Payment
    {
        return $this->subscribe($this->customer(), $this->plan(19.99), 'paypal');
    }

    private function assertUnresolved(Payment $payment): void
    {
        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertSame(SubscriptionStatus::PENDING, $payment->payable->fresh()->status);
        $this->assertSame(0, $this->failedEventsFor($payment));
        $this->assertSame(0, $this->succeededEventsFor($payment));
        $this->assertSame(0, $this->activationsOf($payment->payable_id));
    }
}
