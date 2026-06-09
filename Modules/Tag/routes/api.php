<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tag\Http\Controllers\TagController;
use Modules\Tag\Http\Controllers\TagQueryController;
use Modules\Tag\Http\Controllers\TagTrashedController;

Route::middleware('api')->prefix('api/v1/tags')->group(function () {
    Route::get('active', [TagQueryController::class, 'active']);
    Route::get('popular', [TagQueryController::class, 'popular']);
    Route::get('search', [TagQueryController::class, 'search']);
    Route::get('slug/{slug}', [TagQueryController::class, 'findBySlug']);
});

Route::middleware(['api', 'auth:api'])->prefix('api/v1/admin/tags')->group(function () {
    Route::get('trashed', [TagTrashedController::class, 'index']);
    Route::patch('{tag}/restore', [TagTrashedController::class, 'restore']);
    Route::delete('{tag}/force-delete', [TagTrashedController::class, 'forceDelete']);
    Route::get('active', [TagQueryController::class, 'active']);
    Route::get('inactive', [TagQueryController::class, 'inactive']);

    Route::get('/', [TagController::class, 'index']);
    Route::post('/', [TagController::class, 'store']);
    Route::get('{tag}', [TagController::class, 'show']);
    Route::put('{tag}', [TagController::class, 'update']);
    Route::delete('{tag}', [TagController::class, 'destroy']);
});
