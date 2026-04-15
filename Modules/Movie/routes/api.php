<?php

use Illuminate\Support\Facades\Route;
use Modules\Movie\Http\Controllers\EpisodeController;
use Modules\Movie\Http\Controllers\MovieController;

Route::middleware(['auth:api', 'auto.authorize'])
    ->prefix('api/v1')
    ->name('api.')
    ->group(function () {

        Route::apiResource('movies', MovieController::class);
        Route::post('movies/{movie}/restore', [MovieController::class, 'restore'])
            ->name('movies.restore');

        Route::apiResource('movies.episodes', EpisodeController::class);
        Route::post('movies/{movie}/episodes/{episode}/restore', [EpisodeController::class, 'restore'])
            ->name('movies.episodes.restore');
    });
