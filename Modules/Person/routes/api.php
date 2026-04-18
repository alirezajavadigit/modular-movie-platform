<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Person\Http\Controllers\CreditController;
use Modules\Person\Http\Controllers\CreditQueryController;
use Modules\Person\Http\Controllers\PersonController;
use Modules\Person\Http\Controllers\PersonQueryController;
use Modules\Person\Http\Controllers\PersonTrashedController;

Route::middleware('api')->prefix('api/v1/persons')->group(function () {
    Route::get('active', [PersonQueryController::class, 'active']);
    Route::get('popular', [PersonQueryController::class, 'popular']);
    Route::get('search', [PersonQueryController::class, 'search']);
    Route::get('slug/{slug}', [PersonQueryController::class, 'findBySlug']);
    Route::get('department/{department}', [PersonQueryController::class, 'byDepartment']);
});

Route::middleware('api')->prefix('api/v1/credits')->group(function () {
    Route::get('{creditableType}/{creditableId}/cast', [CreditQueryController::class, 'cast']);
    Route::get('{creditableType}/{creditableId}/crew', [CreditQueryController::class, 'crew']);
    Route::get('{creditableType}/{creditableId}', [CreditQueryController::class, 'forCreditable']);
});

Route::middleware(['api', 'auth:api', 'auto.authorize'])->prefix('api/v1/admin/persons')->group(function () {
    Route::get('/', [PersonController::class, 'index']);
    Route::post('/', [PersonController::class, 'store']);
    Route::get('{person}', [PersonController::class, 'show']);
    Route::put('{person}', [PersonController::class, 'update']);
    Route::delete('{person}', [PersonController::class, 'destroy']);

    Route::get('trashed', [PersonTrashedController::class, 'index']);
    Route::patch('{person}/restore', [PersonTrashedController::class, 'restore']);
    Route::delete('{person}/force-delete', [PersonTrashedController::class, 'forceDelete']);
});

Route::middleware(['api', 'auth:api', 'auto.authorize'])->prefix('api/v1/admin/credits')->group(function () {
    Route::get('/', [CreditController::class, 'index']);
    Route::post('/', [CreditController::class, 'store']);
    Route::get('{credit}', [CreditController::class, 'show']);
    Route::put('{credit}', [CreditController::class, 'update']);
    Route::delete('{credit}', [CreditController::class, 'destroy']);
});
