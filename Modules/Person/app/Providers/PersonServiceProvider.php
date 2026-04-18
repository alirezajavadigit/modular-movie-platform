<?php

namespace Modules\Person\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Route;
use Modules\Person\Contracts\CreditRepositoryInterface;
use Modules\Person\Contracts\CreditServiceInterface;
use Modules\Person\Contracts\PersonRepositoryInterface;
use Modules\Person\Contracts\PersonServiceInterface;
use Modules\Person\Repositories\CreditRepository;
use Modules\Person\Repositories\PersonRepository;
use Modules\Person\Services\CreditService;
use Modules\Person\Services\PersonService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PersonServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Person';

    protected string $nameLower = 'person';

    protected array $providers = [
        EventServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path('Person', 'config/config.php'),
            'person-module',
        );

        $this->loadMigrationsFrom(module_path('Person', 'database/migrations'));

        $this->app->bind(PersonRepositoryInterface::class, PersonRepository::class);
        $this->app->bind(PersonServiceInterface::class, PersonService::class);

        $this->app->bind(CreditRepositoryInterface::class, CreditRepository::class);
        $this->app->bind(CreditServiceInterface::class, CreditService::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->registerMorphMap();

        Route::middleware('api')
            ->group(module_path('Person', '/routes/api.php'));

        if (file_exists($webRoutes = module_path('Person', '/routes/web.php'))) {
            Route::middleware('web')->group($webRoutes);
        }
    }

    protected function registerMorphMap(): void
    {
        $map = (array) config('person-module.morph_map', []);
        $existing = array_filter($map, fn($class) => class_exists($class));

        if (!empty($existing)) {
            Relation::morphMap($existing, false);
        }
    }
}
