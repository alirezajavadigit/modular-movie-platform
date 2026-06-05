<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\UserController;
use Modules\User\Http\Controllers\UserQueryController;
use Modules\User\Http\Controllers\UserTrashedController;

Route::middleware(['api', 'auth:api', 'auto.authorize'])->prefix('api/v1/admin/users')->group(function () {
    Route::get('search', [UserQueryController::class, 'search']);
    Route::get('trashed', [UserTrashedController::class, 'index']);

    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('{user}', [UserController::class, 'show'])->whereNumber('user');
    Route::put('{user}', [UserController::class, 'update'])->whereNumber('user');
    Route::delete('{user}', [UserController::class, 'destroy'])->whereNumber('user');

    Route::patch('{user}/restore', [UserTrashedController::class, 'restore'])
        ->whereNumber('user')
        ->withTrashed();

    Route::delete('{user}/force-delete', [UserTrashedController::class, 'forceDelete'])
        ->whereNumber('user')
        ->withTrashed();
});
