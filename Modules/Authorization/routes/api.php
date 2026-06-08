<?php

use Illuminate\Support\Facades\Route;
use Modules\Authorization\Http\Controllers\PermissionController;
use Modules\Authorization\Http\Controllers\RoleController;
use Modules\Authorization\Http\Controllers\RolePermissionController;
use Modules\Authorization\Http\Controllers\UserAuthorizationController;

Route::middleware(['auth:api'])
    ->prefix('api/v1')
    ->name('api.')
    ->group(function () {

        Route::apiResource('roles', RoleController::class);

        Route::put('roles/{role}/permissions', [RolePermissionController::class, 'sync'])
            ->name('roles.permissions.sync');

        Route::name('permissions.')->prefix('permissions')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->name('index');
            Route::get('/module/{module}', [PermissionController::class, 'byModule'])->name('module');
        });

        Route::prefix('users/{userId}')->name('users.')->group(function () {
            Route::get('roles', [UserAuthorizationController::class, 'getUserRoles'])
                ->name('roles.index');
            Route::post('roles/assign', [UserAuthorizationController::class, 'assignRoles'])
                ->name('roles.assign');
            Route::post('roles/revoke', [UserAuthorizationController::class, 'revokeRoles'])
                ->name('roles.revoke');
            Route::post('roles/sync', [UserAuthorizationController::class, 'syncRoles'])
                ->name('roles.sync');

            Route::get('permissions', [UserAuthorizationController::class, 'getUserPermissions'])
                ->name('permissions.index');
            Route::post('permissions/assign', [UserAuthorizationController::class, 'assignPermissions'])
                ->name('permissions.assign');
            Route::post('permissions/revoke', [UserAuthorizationController::class, 'revokePermissions'])
                ->name('permissions.revoke');
        });
    });
