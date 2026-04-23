<?php

namespace Modules\Favorite\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\Favorite\Contracts\FavoriteRepositoryInterface;
use Modules\Favorite\Contracts\FavoriteServiceInterface;
use Modules\Favorite\Models\Favorite;
use Modules\Favorite\Policies\FavoritePolicy;
use Modules\Favorite\Repositories\FavoriteRepository;
use Modules\Favorite\Services\FavoriteService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class FavoriteServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Favorite';

    protected string $nameLower = 'favorite';

    protected array $providers = [
        EventServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path('Favorite', 'config/config.php'),
            'favorite-module',
        );

        $this->loadMigrationsFrom(module_path('Favorite', 'database/migrations'));

        $this->app->bind(FavoriteRepositoryInterface::class, FavoriteRepository::class);
        $this->app->bind(FavoriteServiceInterface::class, FavoriteService::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->registerMorphMap();
        Gate::policy(Favorite::class, FavoritePolicy::class);

        Route::middleware('api')
            ->group(module_path('Favorite', '/routes/api.php'));
    }

    protected function registerMorphMap(): void
    {
        $map = (array) config('favorite-module.morph_map', []);
        $existing = array_filter($map, fn($class) => class_exists($class));

        if (!empty($existing)) {
            Relation::morphMap($existing, false);
        }
    }
}
