<?php

namespace Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Auth\Console\Commands\CreateUserCommand;
use Modules\Auth\Contracts\AuthServiceInterface;
use Modules\Auth\Contracts\Notification\NotificationChannelInterface;
use Modules\Auth\Contracts\OtpRepositoryInterface;
use Modules\Auth\Contracts\OtpServiceInterface;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\OtpRepository;
use Modules\Auth\Repositories\UserRepository;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\Notification\Channels\EmailChannel;
use Modules\Auth\Services\Notification\Channels\SmsChannel;
use Modules\Auth\Services\OtpService;

class AuthServiceProvider extends ServiceProvider
{
    protected array $commands = [
        CreateUserCommand::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path('Auth', 'config/config.php'),
            'auth-module',
        );

        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(OtpRepositoryInterface::class, OtpRepository::class);
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(OtpServiceInterface::class, OtpService::class);

        $this->app->bind(NotificationChannelInterface::class, function () {
            return match (config('auth-module.notification_channel')) {
                'sms' => new SmsChannel(),
                default => new EmailChannel(),
            };
        });

        $this->commands($this->commands);
    }

    public function boot(): void
    {
        config(['auth.providers.users.model' => User::class]);

        $this->loadMigrationsFrom(module_path('Auth', 'database/migrations'));
        $this->loadTranslationsFrom(module_path('Auth', 'resources/lang'), 'auth-module');

        $this->publishes([
            module_path('Auth', 'config/config.php') => config_path('auth-module.php'),
        ], 'auth-module-config');

        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
    }
}
