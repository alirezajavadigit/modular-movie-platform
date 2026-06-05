<?php

namespace Modules\User\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Models\User;
use Modules\User\Contracts\UserRepositoryInterface;
use Modules\User\Contracts\UserServiceInterface;
use Modules\User\Policies\UserPolicy;
use Modules\User\Repositories\UserRepository;
use Modules\User\Services\UserService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class UserServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'User';

    protected string $nameLower = 'user';

    protected array $providers = [
        EventServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path('User', 'config/config.php'),
            'user-module',
        );

        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(module_path('User', 'resources/lang'), 'user');

        Gate::policy(User::class, UserPolicy::class);

        Route::middleware('api')
            ->group(module_path('User', '/routes/api.php'));
    }
}
