<?php

namespace Modules\Movie\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Modules\Movie\Policies\EpisodePolicy;
use Modules\Movie\Policies\MoviePolicy;

class MoviePolicyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Movie::class, MoviePolicy::class);
        Gate::policy(Episode::class, EpisodePolicy::class);
    }
}
