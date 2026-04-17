<?php

namespace Modules\Tag\Providers;

use Illuminate\Support\Facades\Route;
use Modules\Tag\Contracts\TagRepositoryInterface;
use Modules\Tag\Contracts\TagServiceInterface;
use Modules\Tag\Repositories\TagRepository;
use Modules\Tag\Services\TagService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class TagServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Tag';

    protected string $nameLower = 'tag';

    protected array $providers = [
        EventServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path('Tag', 'config/config.php'),
            'tag-module',
        );

        $this->loadMigrationsFrom(module_path('Tag', 'database/migrations'));

        $this->app->bind(
            TagRepositoryInterface::class,
            TagRepository::class,
        );

        $this->app->bind(
            TagServiceInterface::class,
            TagService::class,
        );
    }

    public function boot(): void
    {
        parent::boot();

        Route::middleware('api')
            ->group(module_path('Tag', '/routes/api.php'));
    }
}
