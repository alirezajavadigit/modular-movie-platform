<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature;

use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Models\User;
use Modules\Authorization\Models\Permission;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Events\PaymentSucceeded;
use Modules\Payment\Models\Payment;
use Modules\Payment\Tests\Support\FakeZarinPalServer;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\SubscriptionPlan;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use ZarinPal\Sdk\ClientBuilder;

final class PaymentVerificationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private FakeZarinPalServer $zarinPal;

    private array $stripeSessions = [];

    private array $stripeCreated = [];

    private array $stripeRetrieved = [];

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        config([
            'payment-module.frontend_redirect_url'            => '',
            'payment-module.gateways.zarinpal.merchant_id'    => '11111111-2222-3333-4444-555555555555',
            'payment-module.gateways.zarinpal.sandbox'        => true,
            'payment-module.gateways.zarinpal.callback_url'   => 'https://app.test/payments/callback/zarinpal',
            'payment-module.gateways.stripe.secret_key'       => 'sk_test_fake',
            'payment-module.gateways.stripe.callback_url'     => 'https://app.test/payments/callback/stripe',
            'payment-module.gateways.stripe.cancel_url'       => 'https://app.test/payments/cancel',
        ]);

        $this->zarinPal = new FakeZarinPalServer();
        $zarinPal = $this->zarinPal;
        $this->app->bind(ClientBuilder::class, function () use ($zarinPal) {
            $factory = new HttpFactory();

            return new ClientBuilder(30, $zarinPal, $factory, $factory);
        });

        Http::preventStrayRequests();
        Http::fake(['api.stripe.com/*' => fn (Request $request) => $this->stripe($request)]);
    }

    public function test_valid_zarinpal_payment_is_verified_with_its_own_authority(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(50000));
        $this->zarinPal->markPaid($payment->transaction_id);

        $successEvents = $this->countSuccessEvents();

        $this->verify($owner, $payment, $payment->transaction_id)->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertSame(SubscriptionStatus::ACTIVE, $payment->payable->status);
        $this->assertSame(1, $successEvents());
    }

    public function test_valid_zarinpal_payment_is_verified_without_sending_a_transaction_id(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(50000));
        $this->zarinPal->markPaid($payment->transaction_id);

        $this->verify($owner, $payment)->assertOk();

        $this->assertSame(PaymentStatus::SUCCESS, $payment->fresh()->status);
    }

    public function test_valid_zarinpal_callback_settles_the_payment(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(50000));
        $this->zarinPal->markPaid($payment->transaction_id);

        $this->zarinPalCallback($payment->transaction_id)->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertSame(SubscriptionStatus::ACTIVE, $payment->payable->status);
    }

    public function test_transaction_id_of_another_payment_is_rejected(): void
    {
        $owner = $this->customer();
        $plan = $this->plan(50000);
        $paymentA = $this->subscribe($owner, $plan);
        $this->zarinPal->markPaid($paymentA->transaction_id);
        $paymentB = $this->subscribe($owner, $plan);
        $authorityB = $paymentB->transaction_id;

        $this->verify($owner, $paymentB, $paymentA->transaction_id)->assertUnprocessable();

        $paymentB->refresh();
        $this->assertSame(PaymentStatus::PENDING, $paymentB->status);
        $this->assertSame($authorityB, $paymentB->transaction_id);
        $this->assertSame(SubscriptionStatus::PENDING, $paymentB->payable->status);
        $this->assertSame(0, $this->zarinPal->verifyCallsFor($paymentA->transaction_id));
        $this->assertFalse($this->zarinPal->isVerified($paymentA->transaction_id));
    }

    public function test_previously_verified_transaction_cannot_settle_another_payment(): void
    {
        $owner = $this->customer();
        $plan = $this->plan(50000);
        $paymentA = $this->subscribe($owner, $plan);
        $this->zarinPal->markPaid($paymentA->transaction_id);
        $this->zarinPalCallback($paymentA->transaction_id)->assertOk();
        $this->assertSame(PaymentStatus::SUCCESS, $paymentA->fresh()->status);

        $paymentB = $this->subscribe($owner, $plan);
        $authorityB = $paymentB->transaction_id;

        $this->verify($owner, $paymentB, $paymentA->transaction_id)->assertUnprocessable();

        $paymentB->refresh();
        $this->assertSame(PaymentStatus::PENDING, $paymentB->status);
        $this->assertSame($authorityB, $paymentB->transaction_id);
        $this->assertSame(SubscriptionStatus::PENDING, $paymentB->payable->status);
    }

    public function test_already_verified_response_is_accepted_for_the_payments_own_authority(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(50000));
        $this->zarinPal->markVerified($payment->transaction_id);

        $this->verify($owner, $payment)->assertOk();

        $this->assertSame(PaymentStatus::SUCCESS, $payment->fresh()->status);
    }

    public function test_invalid_transaction_id_is_rejected_without_changing_the_payment(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(50000));
        $authority = $payment->transaction_id;

        $this->verify($owner, $payment, 'not-a-real-authority')->assertUnprocessable();

        $payment->refresh();
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame($authority, $payment->transaction_id);
        $this->assertSame(SubscriptionStatus::PENDING, $payment->payable->status);
        $this->assertSame(0, $this->zarinPal->verifyCallsFor('not-a-real-authority'));
    }

    public function test_valid_verification_still_works_after_an_invalid_attempt(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(50000));

        $this->verify($owner, $payment, 'not-a-real-authority')->assertUnprocessable();

        $this->zarinPal->markPaid($payment->transaction_id);
        $this->verify($owner, $payment, $payment->transaction_id)->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertSame(SubscriptionStatus::ACTIVE, $payment->payable->status);
    }

    public function test_callback_still_settles_the_payment_after_an_invalid_attempt(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(50000));
        $authority = $payment->transaction_id;

        $this->verify($owner, $payment, 'not-a-real-authority')->assertUnprocessable();
        $this->zarinPal->markPaid($authority);

        $this->zarinPalCallback($authority)->assertOk();

        $this->assertSame(PaymentStatus::SUCCESS, $payment->fresh()->status);
    }

    public function test_verifying_an_unpaid_payment_keeps_it_pending_and_retryable(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(50000));

        $this->verify($owner, $payment)->assertStatus(409);

        $payment->refresh();
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame(SubscriptionStatus::PENDING, $payment->payable->status);

        $this->zarinPal->markPaid($payment->transaction_id);
        $this->verify($owner, $payment)->assertOk();

        $this->assertSame(PaymentStatus::SUCCESS, $payment->fresh()->status);
    }

    public function test_gateway_outage_during_verify_keeps_the_payment_pending_and_retryable(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(50000));
        $this->zarinPal->markPaid($payment->transaction_id);
        $this->zarinPal->setUnavailable(true);

        $this->verify($owner, $payment)->assertStatus(409);

        $payment->refresh();
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame(SubscriptionStatus::PENDING, $payment->payable->status);

        $this->zarinPal->setUnavailable(false);
        $this->verify($owner, $payment)->assertOk();

        $this->assertSame(PaymentStatus::SUCCESS, $payment->fresh()->status);
    }

    public function test_gateway_outage_during_callback_keeps_the_payment_pending_and_retryable(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(50000));
        $authority = $payment->transaction_id;
        $this->zarinPal->markPaid($authority);
        $this->zarinPal->setUnavailable(true);

        $this->zarinPalCallback($authority)->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame(SubscriptionStatus::PENDING, $payment->payable->status);

        $this->zarinPal->setUnavailable(false);
        $this->zarinPalCallback($authority)->assertOk();

        $this->assertSame(PaymentStatus::SUCCESS, $payment->fresh()->status);
    }

    public function test_valid_stripe_payment_with_matching_amount_and_currency_is_verified(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(19.99), 'stripe');

        $this->assertSame('1999', (string) $this->stripeCreated[$payment->transaction_id]['line_items[0][price_data][unit_amount]']);
        $this->assertSame('usd', $this->stripeCreated[$payment->transaction_id]['line_items[0][price_data][currency]']);

        $this->payStripe($payment->transaction_id, 1999, 'usd');

        $this->verify($owner, $payment, $payment->transaction_id)->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertSame(SubscriptionStatus::ACTIVE, $payment->payable->status);
    }

    public function test_stripe_amount_mismatch_never_settles_the_payment(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(50), 'stripe');
        $this->payStripe($payment->transaction_id, 500, 'usd');

        $this->verify($owner, $payment)->assertStatus(409);
        $this->assertNotSame(PaymentStatus::SUCCESS, $payment->fresh()->status);

        $this->stripeCallback($payment->transaction_id)->assertOk();

        $payment->refresh();
        $this->assertNotSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertNotSame(SubscriptionStatus::ACTIVE, $payment->payable->status);
    }

    public function test_stripe_currency_mismatch_never_settles_the_payment(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(50), 'stripe');
        $this->payStripe($payment->transaction_id, 5000, 'eur');

        $this->verify($owner, $payment)->assertStatus(409);

        $payment->refresh();
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame(SubscriptionStatus::PENDING, $payment->payable->status);
    }

    public function test_lower_value_stripe_session_cannot_settle_a_higher_value_payment(): void
    {
        $owner = $this->customer();
        $cheap = $this->subscribe($owner, $this->plan(5), 'stripe');
        $this->payStripe($cheap->transaction_id, 500, 'usd');
        $expensive = $this->subscribe($owner, $this->plan(500), 'stripe');
        $expensiveSession = $expensive->transaction_id;

        $this->verify($owner, $expensive, $cheap->transaction_id)->assertUnprocessable();

        $expensive->refresh();
        $this->assertSame(PaymentStatus::PENDING, $expensive->status);
        $this->assertSame($expensiveSession, $expensive->transaction_id);
        $this->assertSame(SubscriptionStatus::PENDING, $expensive->payable->status);
        $this->assertNotContains($cheap->transaction_id, $this->stripeRetrieved);
    }

    public function test_another_user_cannot_verify_a_payment_they_do_not_own(): void
    {
        $owner = $this->customer();
        $intruder = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(50000));
        $authority = $payment->transaction_id;
        $this->zarinPal->markPaid($authority);

        $this->verify($intruder, $payment, $authority)->assertForbidden();

        $payment->refresh();
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame($authority, $payment->transaction_id);
        $this->assertSame(0, $this->zarinPal->verifyCallsFor($authority));
    }

    public function test_guest_cannot_verify_a_payment(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(50000));
        $this->app['auth']->forgetGuards();

        $this->patchJson("/api/v1/payments/{$payment->id}/verify", ['transaction_id' => $payment->transaction_id])
            ->assertUnauthorized();

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    public function test_user_with_payments_verify_permission_can_verify_another_users_payment(): void
    {
        $owner = $this->customer();
        $payment = $this->subscribe($owner, $this->plan(50000));
        $this->zarinPal->markPaid($payment->transaction_id);

        $staff = User::factory()->create();
        $staff->givePermissionTo('payments.verify');

        $this->verify($staff, $payment)->assertOk();

        $this->assertSame(PaymentStatus::SUCCESS, $payment->fresh()->status);
    }

    public function test_user_with_payments_verify_permission_cannot_use_another_payments_transaction(): void
    {
        $owner = $this->customer();
        $plan = $this->plan(50000);
        $paymentA = $this->subscribe($owner, $plan);
        $this->zarinPal->markVerified($paymentA->transaction_id);
        $paymentB = $this->subscribe($owner, $plan);

        $staff = User::factory()->create();
        $staff->givePermissionTo('payments.verify');

        $this->verify($staff, $paymentB, $paymentA->transaction_id)->assertUnprocessable();

        $this->assertSame(PaymentStatus::PENDING, $paymentB->fresh()->status);
    }

    public function test_owner_is_forbidden_when_the_payments_verify_permission_does_not_exist(): void
    {
        $owner = $this->customer(createVerifyPermission: false);
        $payment = $this->subscribe($owner, $this->plan(50000));

        $this->verify($owner, $payment, $payment->transaction_id)->assertForbidden();

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    private function customer(bool $createVerifyPermission = true): User
    {
        Permission::firstOrCreate(['name' => 'subscriptions.create', 'guard_name' => 'api']);

        if ($createVerifyPermission) {
            Permission::firstOrCreate(['name' => 'payments.verify', 'guard_name' => 'api']);
        }

        $user = User::factory()->create();
        $user->givePermissionTo('subscriptions.create');

        return $user;
    }

    private function plan(float $price): SubscriptionPlan
    {
        return SubscriptionPlan::factory()->create(['price' => $price, 'duration_days' => 30]);
    }

    private function subscribe(User $user, SubscriptionPlan $plan, string $driver = 'zarinpal'): Payment
    {
        $this->actingAs($user, 'api')
            ->postJson('/api/v1/subscriptions/subscribe', ['plan_id' => $plan->id, 'driver' => $driver])
            ->assertOk();

        $this->app['auth']->forgetGuards();

        return Payment::query()->latest('id')->firstOrFail();
    }

    private function verify(User $user, Payment $payment, ?string $transactionId = null): TestResponse
    {
        $body = $transactionId === null ? [] : ['transaction_id' => $transactionId];

        $response = $this->actingAs($user, 'api')->patchJson("/api/v1/payments/{$payment->id}/verify", $body);

        $this->app['auth']->forgetGuards();

        return $response;
    }

    private function zarinPalCallback(string $authority, string $status = 'OK'): TestResponse
    {
        return $this->getJson("/api/v1/payments/callback/zarinpal?Authority={$authority}&Status={$status}");
    }

    private function stripeCallback(string $sessionId): TestResponse
    {
        return $this->getJson("/api/v1/payments/callback/stripe?session_id={$sessionId}");
    }

    private function payStripe(string $sessionId, int $amountTotal, string $currency): void
    {
        $this->stripeSessions[$sessionId] = array_merge($this->stripeSessions[$sessionId], [
            'status'         => 'complete',
            'payment_status' => 'paid',
            'amount_total'   => $amountTotal,
            'currency'       => $currency,
        ]);
    }

    private function stripe(Request $request)
    {
        if ($request->method() === 'POST' && str_ends_with($request->url(), '/checkout/sessions')) {
            $id = 'cs_test_' . (count($this->stripeSessions) + 1);
            $this->stripeCreated[$id] = $request->data();
            $this->stripeSessions[$id] = [
                'id'             => $id,
                'object'         => 'checkout.session',
                'status'         => 'open',
                'payment_status' => 'unpaid',
                'amount_total'   => (int) $request->data()['line_items[0][price_data][unit_amount]'],
                'currency'       => $request->data()['line_items[0][price_data][currency]'],
                'url'            => "https://checkout.stripe.test/{$id}",
            ];

            return Http::response($this->stripeSessions[$id]);
        }

        $id = rawurldecode(basename(parse_url($request->url(), PHP_URL_PATH)));
        $this->stripeRetrieved[] = $id;

        return isset($this->stripeSessions[$id])
            ? Http::response($this->stripeSessions[$id])
            : Http::response(['error' => ['message' => 'No such checkout.session']], 404);
    }

    private function countSuccessEvents(): \Closure
    {
        $count = 0;
        Event::listen(PaymentSucceeded::class, function () use (&$count) {
            $count++;
        });

        return function () use (&$count) {
            return $count;
        };
    }
}
