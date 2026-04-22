<?php

namespace Modules\Authorization\Providers;

use Illuminate\Support\Facades\Route;
use Modules\Authorization\Contracts\PermissionAssignmentServiceInterface;
use Modules\Authorization\Contracts\PermissionRepositoryInterface;
use Modules\Authorization\Contracts\RoleAssignmentServiceInterface;
use Modules\Authorization\Contracts\RoleRepositoryInterface;
use Modules\Authorization\Contracts\RoleServiceInterface;
use Modules\Authorization\Providers\AuthorizationPolicyServiceProvider;
use Modules\Authorization\Repositories\PermissionRepository;
use Modules\Authorization\Repositories\RoleRepository;
use Modules\Authorization\Services\PermissionAssignmentService;
use Modules\Authorization\Services\RoleAssignmentService;
use Modules\Authorization\Services\RoleService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AuthorizationServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Authorization';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'authorization';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        AuthorizationPolicyServiceProvider::class,
    ];

    /**
     * Define module schedules.
     * 
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path('Authorization', 'config/config.php'),
            'authorization-module',
        );

        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(RoleServiceInterface::class, RoleService::class);
        $this->app->bind(RoleAssignmentServiceInterface::class, RoleAssignmentService::class);
        $this->app->bind(PermissionAssignmentServiceInterface::class, PermissionAssignmentService::class);
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(
            module_path('Authorization', 'config/permission.php'),
            'permission',
        );
        $this->app['router']->aliasMiddleware(
            'auto.authorize',
            \Modules\Authorization\Http\Middleware\AutoAuthorizeMiddleware::class,
        );
        Route::middleware('api')
            ->group(module_path('Authorization', '/routes/api.php'));
    }
}
