<?php

namespace Modules\Like\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\Like\Contracts\LikeRepositoryInterface;
use Modules\Like\Contracts\LikeServiceInterface;
use Modules\Like\Models\Like;
use Modules\Like\Policies\LikePolicy;
use Modules\Like\Repositories\LikeRepository;
use Modules\Like\Services\LikeService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class LikeServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Like';

    protected string $nameLower = 'like';

    protected array $providers = [
        EventServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path('Like', 'config/config.php'),
            'like-module',
        );

        $this->loadMigrationsFrom(module_path('Like', 'database/migrations'));

        $this->app->bind(LikeRepositoryInterface::class, LikeRepository::class);
        $this->app->bind(LikeServiceInterface::class, LikeService::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->registerMorphMap();
        Gate::policy(Like::class, LikePolicy::class);

        Route::middleware('api')
            ->group(module_path('Like', '/routes/api.php'));
    }

    protected function registerMorphMap(): void
    {
        $map      = (array) config('like-module.morph_map', []);
        $existing = array_filter($map, fn($class) => class_exists($class));

        if (!empty($existing)) {
            Relation::morphMap($existing, false);
        }
    }
}
