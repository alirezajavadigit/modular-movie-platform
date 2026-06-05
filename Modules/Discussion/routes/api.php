<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Discussion\Http\Controllers\DiscussionController;
use Modules\Discussion\Http\Controllers\DiscussionQueryController;
use Modules\Discussion\Http\Controllers\DiscussionStatusController;
use Modules\Discussion\Http\Controllers\DiscussionTrashedController;

Route::middleware('api')->prefix('api/v1/discussions')->group(function () {
    Route::get('{discussion}/replies', [DiscussionQueryController::class, 'replies'])
        ->whereNumber('discussion');
});

Route::middleware(['api', 'auth:api', 'auto.authorize'])->prefix('api/v1/discussions')->group(function () {
    Route::get('pending/list', [DiscussionQueryController::class, 'pending']);

    Route::get('{discussionableType}/{discussionableId}', [DiscussionQueryController::class, 'byDiscussionable'])
        ->whereNumber('discussionableId');

    Route::get('{discussion}', [DiscussionController::class, 'show'])
        ->whereNumber('discussion');

    Route::post('/', [DiscussionController::class, 'store']);

    Route::put('{discussion}', [DiscussionController::class, 'update'])
        ->whereNumber('discussion');

    Route::delete('{discussion}', [DiscussionController::class, 'destroy'])
        ->whereNumber('discussion');

    Route::delete('{discussion}/force', [DiscussionTrashedController::class, 'forceDelete'])
        ->whereNumber('discussion')
        ->withTrashed();

    Route::post('{discussion}/restore', [DiscussionTrashedController::class, 'restore'])
        ->whereNumber('discussion')
        ->withTrashed();

    Route::post('{discussion}/approve', [DiscussionStatusController::class, 'approve'])
        ->whereNumber('discussion');

    Route::post('{discussion}/reject', [DiscussionStatusController::class, 'reject'])
        ->whereNumber('discussion');

    Route::post('{discussion}/pending', [DiscussionStatusController::class, 'markAsPending'])
        ->whereNumber('discussion');
});

Route::middleware(['api', 'auth:api', 'auto.authorize'])->prefix('api/v1/admin/discussions')->group(function () {
    Route::get('approved', [DiscussionQueryController::class, 'approved']);
    Route::get('rejected', [DiscussionQueryController::class, 'rejected']);

    Route::get('user/{userId}', [DiscussionQueryController::class, 'byUser'])
        ->whereNumber('userId');
});
