<?php

namespace Modules\Movie\Providers;

use Illuminate\Support\Facades\Route;
use Modules\Movie\Contracts\EpisodeRepositoryInterface;
use Modules\Movie\Contracts\EpisodeServiceInterface;
use Modules\Movie\Contracts\FileUploadServiceInterface;
use Modules\Movie\Contracts\MovieRepositoryInterface;
use Modules\Movie\Contracts\MovieServiceInterface;
use Modules\Movie\Repositories\EpisodeRepository;
use Modules\Movie\Repositories\MovieRepository;
use Modules\Movie\Services\EpisodeService;
use Modules\Movie\Services\FileUploadService;
use Modules\Movie\Services\MovieService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class MovieServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Movie';

    protected string $nameLower = 'movie';

    protected array $providers = [
        EventServiceProvider::class,
        MoviePolicyServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path('Movie', 'config/config.php'),
            'movie',
        );

        $this->app->bind(MovieRepositoryInterface::class, MovieRepository::class);
        $this->app->bind(MovieServiceInterface::class, MovieService::class);
        $this->app->bind(EpisodeRepositoryInterface::class, EpisodeRepository::class);
        $this->app->bind(EpisodeServiceInterface::class, EpisodeService::class);
        $this->app->bind(FileUploadServiceInterface::class, FileUploadService::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(module_path('Movie', 'resources/lang'), 'movie');

        Route::middleware('api')
            ->group(module_path('Movie', '/routes/api.php'));
    }
}
