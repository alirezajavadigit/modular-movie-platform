<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Article\Http\Controllers\ArticleController;
use Modules\Article\Http\Controllers\ArticleStatusController;
use Modules\Article\Http\Controllers\ArticleTrashedController;
use Modules\Article\Http\Controllers\ArticleQueryController;

Route::middleware('api')->prefix('api/v1/articles')->group(function () {

    Route::get('published', [ArticleQueryController::class, 'published']);
    Route::get('slug/{slug}', [ArticleQueryController::class, 'findBySlug']);
    Route::get('{article}/related', [ArticleQueryController::class, 'related']);
    Route::get('author/{userId}', [ArticleQueryController::class, 'byAuthor']);
    Route::get('search', [ArticleQueryController::class, 'search']);
});

Route::middleware(['api', 'auth:api'])->prefix('api/v1/admin/articles')->group(function () {

    Route::get('/', [ArticleController::class, 'index']);
    Route::post('/', [ArticleController::class, 'store']);
    Route::get('{article}', [ArticleController::class, 'show']);
    Route::put('{article}', [ArticleController::class, 'update']);
    Route::delete('{article}', [ArticleController::class, 'destroy']);

    Route::get('drafts', [ArticleQueryController::class, 'drafts']);
    Route::get('archived', [ArticleQueryController::class, 'archived']);
    Route::get('status/{status}', [ArticleQueryController::class, 'byStatus']);

    Route::patch('{article}/publish', [ArticleStatusController::class, 'publish']);
    Route::patch('{article}/archive', [ArticleStatusController::class, 'archive']);
    Route::patch('{article}/draft', [ArticleStatusController::class, 'markAsDraft']);

    Route::get('trashed', [ArticleTrashedController::class, 'trashed']);
    Route::patch('{article}/restore', [ArticleTrashedController::class, 'restore']);
    Route::delete('{article}/force-delete', [ArticleTrashedController::class, 'forceDelete']);
});
