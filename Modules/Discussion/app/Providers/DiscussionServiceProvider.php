<?php

namespace Modules\Discussion\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\Discussion\Contracts\DiscussionRepositoryInterface;
use Modules\Discussion\Contracts\DiscussionServiceInterface;
use Modules\Discussion\Models\Discussion;
use Modules\Discussion\Policies\DiscussionPolicy;
use Modules\Discussion\Repositories\DiscussionRepository;
use Modules\Discussion\Services\DiscussionService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class DiscussionServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Discussion';

    protected string $nameLower = 'discussion';

    protected array $providers = [
        EventServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path('Discussion', 'config/config.php'),
            'discussion-module',
        );

        $this->loadMigrationsFrom(module_path('Discussion', 'database/migrations'));

        $this->app->bind(DiscussionRepositoryInterface::class, DiscussionRepository::class);
        $this->app->bind(DiscussionServiceInterface::class, DiscussionService::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(module_path('Discussion', 'resources/lang'), 'discussion');

        Gate::policy(Discussion::class, DiscussionPolicy::class);

        Route::middleware('api')
            ->group(module_path('Discussion', '/routes/api.php'));
    }
}
