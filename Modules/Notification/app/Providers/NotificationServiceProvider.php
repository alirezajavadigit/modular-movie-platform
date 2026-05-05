<?php

namespace Modules\Notification\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\Notification\Contracts\NotificationRepositoryInterface;
use Modules\Notification\Contracts\NotificationServiceInterface;
use Modules\Notification\Models\Notification;
use Modules\Notification\Policies\NotificationPolicy;
use Modules\Notification\Repositories\NotificationRepository;
use Modules\Notification\Services\NotificationService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class NotificationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Notification';

    protected string $nameLower = 'notification';

    protected array $providers = [
        EventServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path('Notification', 'config/config.php'),
            'notification-module',
        );

        $this->loadMigrationsFrom(module_path('Notification', 'database/migrations'));

        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(NotificationServiceInterface::class, NotificationService::class);
    }

    public function boot(): void
    {
        parent::boot();

        Route::middleware('api')
            ->group(module_path('Notification', '/routes/api.php'));

        Gate::policy(Notification::class, NotificationPolicy::class);

        $this->registerMorphMap();
        $this->registerRouteModelBindings();
    }

    private function registerMorphMap(): void
    {
        $morphMap = config('notification-module.morph_map', []);

        $filtered = array_filter($morphMap, fn(string $class) => class_exists($class));

        if (!empty($filtered)) {
            Relation::morphMap($filtered);
        }
    }

    private function registerRouteModelBindings(): void
    {
        Route::bind('notification', function (string $value): Notification {
            return Notification::findOrFail($value);
        });

        Route::bind('trashedNotification', function (string $value): Notification {
            return Notification::withTrashed()->findOrFail($value);
        });
    }
}
