<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\NotificationController;
use Modules\Notification\Http\Controllers\NotificationQueryController;
use Modules\Notification\Http\Controllers\NotificationStatusController;
use Modules\Notification\Http\Controllers\NotificationTrashedController;

Route::middleware(['api', 'auth:api', 'auto.authorize'])
    ->prefix('api/v1/admin/notifications')
    ->group(function () {


        Route::get('filter/notifiable', [NotificationQueryController::class, 'forNotifiable']);
        Route::get('filter/unread', [NotificationQueryController::class, 'unread']);
        Route::get('filter/by-type', [NotificationQueryController::class, 'byType']);
        Route::get('meta/types', [NotificationQueryController::class, 'types']);

        Route::patch('{notification}/read', [NotificationStatusController::class, 'markRead']);
        Route::patch('read-all', [NotificationStatusController::class, 'markAllRead']);

        Route::get('trashed', [NotificationTrashedController::class, 'index']);
        Route::patch('{notification}/restore', [NotificationTrashedController::class, 'restore']);
        Route::delete('{notification}/force-delete', [NotificationTrashedController::class, 'forceDelete']);

        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/', [NotificationController::class, 'store']);
        Route::get('{notification}', [NotificationController::class, 'show']);
        Route::put('{notification}', [NotificationController::class, 'update']);
        Route::delete('{notification}', [NotificationController::class, 'destroy']);
    });
