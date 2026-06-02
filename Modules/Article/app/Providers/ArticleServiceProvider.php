<?php

namespace Modules\Article\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\Article\Contracts\ArticleRepositoryInterface;
use Modules\Article\Contracts\ArticleServiceInterface;
use Modules\Article\Models\Article;
use Modules\Article\Policies\ArticlePolicy;
use Modules\Article\Repositories\ArticleRepository;
use Modules\Article\Services\ArticleService;
use Nwidart\Modules\Support\ModuleServiceProvider;

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

        Gate::policy(Article::class, ArticlePolicy::class);

        Route::middleware('api')
            ->group(module_path('Article', '/routes/api.php'));
    }
}
