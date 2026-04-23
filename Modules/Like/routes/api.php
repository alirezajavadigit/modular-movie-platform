<?php

use Illuminate\Support\Facades\Route;
use Modules\Like\Http\Controllers\LikeController;

Route::middleware(['auth:api'])
    ->prefix('api/v1/likes')
    ->name('api.v1.likes.')
    ->group(function () {
        Route::get('/', [LikeController::class, 'index'])->name('index');
        Route::post('/toggle', [LikeController::class, 'toggle'])->name('toggle');
        Route::post('/', [LikeController::class, 'store'])->name('store');
        Route::delete('/{like}', [LikeController::class, 'destroy'])->name('destroy');
    });
