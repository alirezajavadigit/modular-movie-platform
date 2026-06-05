<?php

use Illuminate\Support\Facades\Route;
use Modules\Favorite\Http\Controllers\FavoriteController;

Route::middleware(['auth:api'])
    ->prefix('api/v1/favorites')
    ->name('api.v1.favorites.')
    ->group(function () {
        Route::get('/', [FavoriteController::class, 'index'])->name('index');
        Route::post('/toggle', [FavoriteController::class, 'toggle'])->name('toggle');
        Route::post('/', [FavoriteController::class, 'store'])->name('store');
        Route::delete('/{favorite}', [FavoriteController::class, 'destroy'])->name('destroy');
    });
