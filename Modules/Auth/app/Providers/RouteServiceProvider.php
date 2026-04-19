<?php

namespace Modules\Auth\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();

        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiRoutes();
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(module_path('Auth', 'routes/api.php'));
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('auth.register', function (Request $request) {
            $config = config('auth-module.rate_limits.register');

            return Limit::perMinutes($config['decay_minutes'], $config['max_attempts'])
                ->by($request->ip());
        });

        RateLimiter::for('auth.login', function (Request $request) {
            $config = config('auth-module.rate_limits.login');

            return Limit::perMinutes($config['decay_minutes'], $config['max_attempts'])
                ->by($request->ip());
        });

        RateLimiter::for('auth.refresh', function (Request $request) {
            $config = config('auth-module.rate_limits.refresh');

            return Limit::perMinutes($config['decay_minutes'], $config['max_attempts'])
                ->by($request->ip());
        });

        RateLimiter::for('auth.forgot-password', function (Request $request) {
            $config = config('auth-module.rate_limits.forgot_password');

            return Limit::perMinutes($config['decay_minutes'], $config['max_attempts'])
                ->by($request->ip());
        });
    }
}
