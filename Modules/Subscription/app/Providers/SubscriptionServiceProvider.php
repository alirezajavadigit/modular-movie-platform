<?php

namespace Modules\Subscription\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\Subscription\Contracts\SubscriptionPlanRepositoryInterface;
use Modules\Subscription\Contracts\SubscriptionPlanServiceInterface;
use Modules\Subscription\Contracts\SubscriptionRepositoryInterface;
use Modules\Subscription\Contracts\SubscriptionServiceInterface;
use Modules\Subscription\Models\Subscription;
use Modules\Subscription\Models\SubscriptionPlan;
use Modules\Subscription\Policies\SubscriptionPlanPolicy;
use Modules\Subscription\Policies\SubscriptionPolicy;
use Modules\Subscription\Repositories\SubscriptionPlanRepository;
use Modules\Subscription\Repositories\SubscriptionRepository;
use Modules\Subscription\Services\SubscriptionPlanService;
use Modules\Subscription\Services\SubscriptionService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SubscriptionServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Subscription';

    protected string $nameLower = 'subscription';

    protected array $providers = [
        EventServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path('Subscription', 'config/config.php'),
            'subscription-module',
        );

        $this->loadMigrationsFrom(module_path('Subscription', 'database/migrations'));

        $this->app->bind(SubscriptionPlanRepositoryInterface::class, SubscriptionPlanRepository::class);
        $this->app->bind(SubscriptionPlanServiceInterface::class, SubscriptionPlanService::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, SubscriptionRepository::class);
        $this->app->bind(SubscriptionServiceInterface::class, SubscriptionService::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Subscription::class, SubscriptionPolicy::class);
        Gate::policy(SubscriptionPlan::class, SubscriptionPlanPolicy::class);

        $this->registerRouteModelBindings();

        Route::middleware('api')
            ->group(module_path('Subscription', '/routes/api.php'));
    }

    private function registerRouteModelBindings(): void
    {
        Route::bind('subscription', fn(string $value) => Subscription::withTrashed()->findOrFail($value));
        Route::bind('subscriptionPlan', fn(string $value) => SubscriptionPlan::withTrashed()->findOrFail($value));
    }
}
