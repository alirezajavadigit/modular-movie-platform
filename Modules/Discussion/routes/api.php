<?php

use Illuminate\Support\Facades\Route;
use Modules\Discussion\Http\Controllers\DiscussionController;

Route::middleware(['auth:api'])
    ->prefix('api/v1/discussions')
    ->name('api.v1.discussions.')
    ->group(function () {
        Route::get('/pending/list', [DiscussionController::class, 'pending'])->name('pending.list');
        Route::get('/user/{userId}', [DiscussionController::class, 'userDiscussions'])
            ->whereNumber('userId')
            ->name('user');

        Route::get('/{discussionableType}/{discussionableId}', [DiscussionController::class, 'index'])
            ->whereNumber('discussionableId')
            ->name('index');

        Route::post('/', [DiscussionController::class, 'store'])->name('store');

        Route::get('/{discussion}', [DiscussionController::class, 'show'])
            ->whereNumber('discussion')
            ->name('show');

        Route::put('/{discussion}', [DiscussionController::class, 'update'])
            ->whereNumber('discussion')
            ->name('update');

        Route::delete('/{discussion}', [DiscussionController::class, 'destroy'])
            ->whereNumber('discussion')
            ->name('destroy');

        Route::get('/{discussion}/replies', [DiscussionController::class, 'replies'])
            ->whereNumber('discussion')
            ->name('replies');

        Route::post('/{discussion}/approve', [DiscussionController::class, 'approve'])
            ->whereNumber('discussion')
            ->name('approve');

        Route::post('/{discussion}/reject', [DiscussionController::class, 'reject'])
            ->whereNumber('discussion')
            ->name('reject');

        Route::post('/{discussion}/pending', [DiscussionController::class, 'markAsPending'])
            ->whereNumber('discussion')
            ->name('pending');

        Route::delete('/{discussion}/force', [DiscussionController::class, 'forceDelete'])
            ->whereNumber('discussion')
            ->withTrashed()
            ->name('force-delete');

        Route::post('/{discussion}/restore', [DiscussionController::class, 'restore'])
            ->whereNumber('discussion')
            ->withTrashed()
            ->name('restore');
    });
