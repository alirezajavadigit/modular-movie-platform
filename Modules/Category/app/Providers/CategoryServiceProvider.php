<?php

namespace Modules\Category\Providers;

use Illuminate\Support\Facades\Route;
use Modules\Category\Contracts\CategoryRepositoryInterface;
use Modules\Category\Contracts\CategoryServiceInterface;
use Modules\Category\Repositories\CategoryRepository;
use Modules\Category\Services\CategoryService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class CategoryServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Category';

    protected string $nameLower = 'category';

    protected array $providers = [
        EventServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path('Category', 'config/config.php'),
            'category-module',
        );

        $this->loadMigrationsFrom(module_path('Category', 'database/migrations'));

        $this->app->bind(
            CategoryRepositoryInterface::class,
            CategoryRepository::class,
        );

        $this->app->bind(
            CategoryServiceInterface::class,
            CategoryService::class,
        );
    }

    public function boot(): void
    {
        parent::boot();

        Route::middleware('api')
            ->group(module_path('Category', '/routes/api.php'));
    }
}
