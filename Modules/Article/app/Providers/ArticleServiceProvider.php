<?php

namespace Modules\Article\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Modules\Article\Contracts\ArticleRepositoryInterface;
use Modules\Article\Contracts\ArticleServiceInterface;
use Modules\Article\Repositories\ArticleRepository;
use Modules\Article\Services\ArticleService;
use Illuminate\Support\Facades\Route;

class ArticleServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Article';

    protected string $nameLower = 'article';

    protected array $providers = [
        EventServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();
        $this->mergeConfigFrom(
            module_path('Article', 'config/config.php'),
            'article-module',
        );

        $this->loadMigrationsFrom(module_path('Article', 'database/migrations'));

        $this->app->bind(
            ArticleRepositoryInterface::class,
            ArticleRepository::class,
        );

        $this->app->bind(
            ArticleServiceInterface::class,
            ArticleService::class,
        );
    }

    public function boot(): void
    {
        parent::boot();
        Route::middleware('api')
            ->group(module_path('Article', '/routes/api.php'));
    }
}
