<?php

namespace Modules\Discussion\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Discussion\Models\Discussion;
use Modules\Discussion\Policies\DiscussionPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Discussion::class => DiscussionPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
