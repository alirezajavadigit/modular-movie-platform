<?php

namespace Modules\Authorization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class AutoAuthorizeMiddleware
{
    private array $explicitMap = [
        'UserAuthorizationController' => \Modules\Authorization\Models\Role::class,
    ];

    private array $abilityMap = [
        'index'             => 'viewAny',
        'show'              => 'view',
        'store'             => 'create',
        'update'            => 'update',
        'destroy'           => 'delete',
        'syncPermissions'   => 'syncPermissions',
        'assignRoles'       => 'assignToUser',
        'revokeRoles'       => 'revokeFromUser',
        'syncRoles'         => 'assignToUser',
        'assignPermissions' => 'assignToUser',
        'revokePermissions' => 'revokeFromUser',
        'getUserRoles'      => 'viewAny',
        'getUserPermissions' => 'viewAny',
        'byModule'          => 'viewAny',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        $route = $request->route();

        if (!$route || !$route->getControllerClass()) {
            return $next($request);
        }

        $action = $route->getActionMethod();
        $controllerClass = $route->getControllerClass();
        $controllerBasename = class_basename($controllerClass);

        $modelClass = $this->explicitMap[$controllerBasename]
            ?? $this->resolveModelFromController($controllerClass);

        if (!$modelClass || !$this->policyExists($modelClass)) {
            return $next($request);
        }

        $ability = $this->abilityMap[$action] ?? $action;

        $modelMethods = ['show', 'update', 'destroy'];

        if (in_array($action, $modelMethods)) {
            $routeParam = Str::camel(class_basename($modelClass));
            $paramValue  = $route->parameter($routeParam);

            if (is_numeric($paramValue)) {
                $model = $modelClass::find((int) $paramValue);
                if (!$model) {
                    return $next($request);
                }
            } else {
                $model = $paramValue ?? $modelClass;
            }

            Gate::authorize($ability, $model);
        } else {
            Gate::authorize($ability, $modelClass);
        }

        return $next($request);
    }

    private function resolveModelFromController(string $controllerClass): ?string
    {
        $basename = Str::beforeLast(class_basename($controllerClass), 'Controller');
        $namespaceRoot = Str::beforeLast($controllerClass, '\\Http\\Controllers');
        $modelClass = $namespaceRoot . '\\Models\\' . $basename;

        return class_exists($modelClass) ? $modelClass : null;
    }

    private function policyExists(string $modelClass): bool
    {
        return Gate::getPolicyFor($modelClass) !== null;
    }
}
