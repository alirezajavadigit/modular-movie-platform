<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Category\Http\Controllers\CategoryController;
use Modules\Category\Http\Controllers\CategoryQueryController;
use Modules\Category\Http\Controllers\CategoryTrashedController;

Route::middleware('api')->prefix('api/v1/categories')->group(function () {
    Route::get('active', [CategoryQueryController::class, 'active']);
    Route::get('tree', [CategoryQueryController::class, 'tree']);
    Route::get('search', [CategoryQueryController::class, 'search']);
    Route::get('slug/{slug}', [CategoryQueryController::class, 'findBySlug']);
    Route::get('parent/{parentId?}', [CategoryQueryController::class, 'byParent']);
});

Route::middleware(['api', 'auth:api', 'auto.authorize'])->prefix('api/v1/admin/categories')->group(function () {
    Route::get('trashed', [CategoryTrashedController::class, 'index']);
    Route::patch('{category}/restore', [CategoryTrashedController::class, 'restore']);
    Route::delete('{category}/force-delete', [CategoryTrashedController::class, 'forceDelete']);


    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/', [CategoryController::class, 'store']);
    Route::get('{category}', [CategoryController::class, 'show']);
    Route::put('{category}', [CategoryController::class, 'update']);
    Route::delete('{category}', [CategoryController::class, 'destroy']);
});
