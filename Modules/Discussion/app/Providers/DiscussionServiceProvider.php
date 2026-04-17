<?php

namespace Modules\Discussion\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Discussion\Contracts\DiscussionRepositoryInterface;
use Modules\Discussion\Contracts\DiscussionServiceInterface;
use Modules\Discussion\Repositories\DiscussionRepository;
use Modules\Discussion\Services\DiscussionService;

class DiscussionServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Discussion';

    protected string $moduleNameLower = 'discussion';

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(AuthServiceProvider::class);

        $this->registerConfig();
        $this->registerBindings();
    }

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerMigrations();
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower . '-module.php'),
        ], 'discussion-module-config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower . '-module'
        );
    }

    protected function registerBindings(): void
    {
        $this->app->bind(DiscussionRepositoryInterface::class, DiscussionRepository::class);
        $this->app->bind(DiscussionServiceInterface::class, DiscussionService::class);
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower . '-module');
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(
                module_path($this->moduleName, 'resources/lang'),
                $this->moduleNameLower . '-module'
            );
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'resources/lang'));
        }
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
    }

    public function provides(): array
    {
        return [
            DiscussionRepositoryInterface::class,
            DiscussionServiceInterface::class,
        ];
    }
}
