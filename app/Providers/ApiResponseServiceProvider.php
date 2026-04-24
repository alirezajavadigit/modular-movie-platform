<?php

namespace App\Providers;

use App\Facades\ApiResponse;
use App\Services\ApiResponseService;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class ApiResponseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ApiResponseService::class);

        AliasLoader::getInstance()->alias('ApiResponse', ApiResponse::class);
    }
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
