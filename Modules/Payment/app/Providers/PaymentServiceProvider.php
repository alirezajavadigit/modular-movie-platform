<?php

namespace Modules\Payment\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\Payment\Contracts\PaymentRepositoryInterface;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Payment\Gateways\PayPalGateway;
use Modules\Payment\Gateways\StripeGateway;
use Modules\Payment\Gateways\ZarinPalGateway;
use Modules\Payment\Gateways\ZibalGateway;
use Modules\Payment\Models\Payment;
use Modules\Payment\Policies\PaymentPolicy;
use Modules\Payment\Repositories\PaymentRepository;
use Modules\Payment\Services\PaymentService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PaymentServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Payment';

    protected string $nameLower = 'payment';

    protected array $providers = [
        EventServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path('Payment', 'config/config.php'),
            'payment-module',
        );

        $this->loadMigrationsFrom(module_path('Payment', 'database/migrations'));

        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->bind(PaymentServiceInterface::class, PaymentService::class);

        $this->app->bind('payment.gateway.zibal', ZibalGateway::class);
        $this->app->bind('payment.gateway.zarinpal', ZarinPalGateway::class);
        $this->app->bind('payment.gateway.paypal', PayPalGateway::class);
        $this->app->bind('payment.gateway.stripe', StripeGateway::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Payment::class, PaymentPolicy::class);

        Route::middleware('api')
            ->group(module_path('Payment', '/routes/api.php'));
    }
}
