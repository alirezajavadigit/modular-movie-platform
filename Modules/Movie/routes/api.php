<?php

use Illuminate\Support\Facades\Route;
use Modules\Movie\Http\Controllers\AdminMovieController;
use Modules\Movie\Http\Controllers\EpisodeController;
use Modules\Movie\Http\Controllers\MovieController;
use Modules\Movie\Http\Controllers\MovieTrashedController;
use Modules\Movie\Http\Controllers\PublicEpisodeController;
use Modules\Movie\Http\Controllers\PublicMovieController;

Route::prefix('api/v1')
    ->name('api.')
    ->group(function () {

        Route::name('public.')->group(function () {
            Route::get('movies/search', [PublicMovieController::class, 'search'])->name('movies.search');
            Route::get('movies', [PublicMovieController::class, 'index'])->name('movies.index');
            Route::get('movies/{movie}', [PublicMovieController::class, 'show'])->name('movies.show');
            Route::get('movies/{movie}/episodes', [PublicEpisodeController::class, 'index'])->name('movies.episodes.index');
            Route::get('movies/{movie}/episodes/{episode}', [PublicEpisodeController::class, 'show'])->name('movies.episodes.show');
        });

        Route::middleware(['auth:api'])->group(function () {
            Route::apiResource('movies', MovieController::class)->except(['index', 'show']);
            Route::post('movies/{movie}/restore', [MovieController::class, 'restore'])
                ->name('movies.restore')
                ->withTrashed();

            Route::apiResource('movies.episodes', EpisodeController::class)->except(['index', 'show']);
            Route::post('movies/{movie}/episodes/{episode}/restore', [EpisodeController::class, 'restore'])
                ->name('movies.episodes.restore')
                ->withTrashed();

            Route::prefix('admin')->name('admin.')->group(function () {
                Route::get('movies/trashed', [MovieTrashedController::class, 'index'])->name('movies.trashed');
                Route::patch('movies/{movie}/restore', [MovieTrashedController::class, 'restore'])->name('movies.restore')->withTrashed();
                Route::delete('movies/{movie}/force-delete', [MovieTrashedController::class, 'forceDelete'])->name('movies.force-delete')->withTrashed();
                Route::get('movies', [AdminMovieController::class, 'index'])->name('movies.index');
                Route::get('movies/{movie}', [AdminMovieController::class, 'show'])->name('movies.show')->withTrashed();
            });
        });
    });
