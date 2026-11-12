<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Support;

use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Models\User;
use Modules\Authorization\Models\Permission;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Events\PaymentSucceeded;
use Modules\Payment\Models\Payment;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Subscription;
use Modules\Subscription\Models\SubscriptionPlan;
use Spatie\Permission\PermissionRegistrar;
use ZarinPal\Sdk\ClientBuilder;

trait InteractsWithPaymentGateways
{
    protected FakeZarinPalServer $zarinPal;

    protected FakeHttpGateways $http;

    protected array $observed = ['succeeded' => [], 'failed' => [], 'activated' => []];

    protected function setUpPaymentGateways(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        config([
            'payment-module.frontend_redirect_url'          => '',
            'payment-module.gateways.zarinpal.merchant_id'  => '11111111-2222-3333-4444-555555555555',
            'payment-module.gateways.zarinpal.sandbox'      => true,
            'payment-module.gateways.zarinpal.callback_url' => 'https://app.test/payments/callback/zarinpal',
            'payment-module.gateways.stripe.secret_key'     => 'sk_test_fake',
            'payment-module.gateways.stripe.callback_url'   => 'https://app.test/payments/callback/stripe',
            'payment-module.gateways.stripe.cancel_url'     => 'https://app.test/payments/cancel',
            'payment-module.gateways.paypal.client_id'      => 'paypal-client',
            'payment-module.gateways.paypal.client_secret'  => 'paypal-secret',
            'payment-module.gateways.paypal.mode'           => 'sandbox',
            'payment-module.gateways.paypal.callback_url'   => 'https://app.test/payments/callback/paypal',
            'payment-module.gateways.paypal.cancel_url'     => 'https://app.test/payments/cancel',
            'payment-module.gateways.zibal.merchant'        => 'zibal',
            'payment-module.gateways.zibal.callback_url'    => 'https://app.test/payments/callback/zibal',
        ]);

        $this->zarinPal = new FakeZarinPalServer();
        $zarinPal = $this->zarinPal;
        $this->app->bind(ClientBuilder::class, function () use ($zarinPal) {
            $factory = new HttpFactory();

            return new ClientBuilder(30, $zarinPal, $factory, $factory);
        });

        $this->http = new FakeHttpGateways();
        $this->http->register();

        $this->app['events']->listen(PaymentSucceeded::class, function (PaymentSucceeded $event) {
            $this->observed['succeeded'][] = $event->payment->id;
        });
        $this->app['events']->listen(PaymentFailed::class, function (PaymentFailed $event) {
            $this->observed['failed'][] = $event->payment->id;
        });
        Subscription::updated(function (Subscription $subscription) {
            if ($subscription->wasChanged('status') && $subscription->status === SubscriptionStatus::ACTIVE) {
                $this->observed['activated'][] = $subscription->id;
            }
        });
    }

    protected function customer(): User
    {
        Permission::firstOrCreate(['name' => 'subscriptions.create', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'payments.verify', 'guard_name' => 'api']);

        $user = User::factory()->create();
        $user->givePermissionTo('subscriptions.create');

        return $user;
    }

    protected function plan(float $price = 50000): SubscriptionPlan
    {
        return SubscriptionPlan::factory()->create(['price' => $price, 'duration_days' => 30]);
    }

    protected function subscribe(User $user, SubscriptionPlan $plan, string $driver = 'zarinpal'): Payment
    {
        $this->actingAs($user, 'api')
            ->postJson('/api/v1/subscriptions/subscribe', ['plan_id' => $plan->id, 'driver' => $driver])
            ->assertOk();

        $this->app['auth']->forgetGuards();

        return Payment::query()->latest('id')->firstOrFail();
    }

    protected function payAtGateway(Payment $payment): void
    {
        match ($payment->driver) {
            'zarinpal' => $this->zarinPal->markPaid($payment->transaction_id),
            'stripe'   => $this->http->payStripe($payment->transaction_id),
            'paypal'   => $this->http->approvePayPal($payment->transaction_id),
            'zibal'    => $this->http->payZibal($payment->transaction_id),
        };
    }

    protected function failGateway(string $driver, int|string|null $failure): void
    {
        if ($driver === 'zarinpal') {
            $this->zarinPal->setTimingOut($failure === 'timeout');
            $this->zarinPal->failWithStatus(is_int($failure) ? $failure : null);

            return;
        }

        $this->http->failWith($failure);
    }

    protected function successCallback(Payment $payment): TestResponse
    {
        $reference = $payment->transaction_id;

        return $this->getJson(match ($payment->driver) {
            'zarinpal' => "/api/v1/payments/callback/zarinpal?Authority={$reference}&Status=OK",
            'stripe'   => "/api/v1/payments/callback/stripe?session_id={$reference}",
            'paypal'   => "/api/v1/payments/callback/paypal?token={$reference}&PayerID=PAYER123",
            'zibal'    => "/api/v1/payments/callback/zibal?trackId={$reference}&success=1&status=2",
        });
    }

    protected function cancelCallback(string $driver, string $reference): TestResponse
    {
        return $this->getJson(match ($driver) {
            'zarinpal' => "/api/v1/payments/callback/zarinpal?Authority={$reference}&Status=NOK",
            'paypal'   => "/api/v1/payments/callback/paypal?token={$reference}",
            'zibal'    => "/api/v1/payments/callback/zibal?trackId={$reference}&success=0&status=3",
        });
    }

    protected function verifyAs(User $user, Payment $payment): TestResponse
    {
        $response = $this->actingAs($user, 'api')->patchJson("/api/v1/payments/{$payment->id}/verify");

        $this->app['auth']->forgetGuards();

        return $response;
    }

    protected function succeededEventsFor(Payment $payment): int
    {
        return count(array_keys($this->observed['succeeded'], $payment->id, true));
    }

    protected function failedEventsFor(Payment $payment): int
    {
        return count(array_keys($this->observed['failed'], $payment->id, true));
    }

    protected function activationsOf(int $subscriptionId): int
    {
        return count(array_keys($this->observed['activated'], $subscriptionId, true));
    }
}
