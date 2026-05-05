<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Http\Controllers\SubscriptionController;
use Modules\Subscription\Http\Controllers\SubscriptionPlanController;
use Modules\Subscription\Http\Controllers\SubscriptionPlanQueryController;
use Modules\Subscription\Http\Controllers\SubscriptionPlanStatusController;
use Modules\Subscription\Http\Controllers\SubscriptionPlanTrashedController;
use Modules\Subscription\Http\Controllers\SubscriptionQueryController;
use Modules\Subscription\Http\Controllers\SubscriptionStatusController;
use Modules\Subscription\Http\Controllers\SubscriptionTrashedController;

Route::middleware('api')->prefix('api/v1/subscription-plans')->group(function () {
    Route::get('/', [SubscriptionPlanQueryController::class, 'index']);
    Route::get('{id}', [SubscriptionPlanQueryController::class, 'show']);
});

Route::middleware(['api', 'auth:api'])->prefix('api/v1/subscriptions')->group(function () {
    Route::get('/', [SubscriptionQueryController::class, 'index']);
    Route::get('{id}', [SubscriptionQueryController::class, 'show']);
    Route::post('/subscribe', [SubscriptionStatusController::class, 'subscribe']);
    Route::patch('{id}/activate', [SubscriptionStatusController::class, 'activate']);
    Route::patch('{id}/cancel', [SubscriptionStatusController::class, 'cancel']);
});

Route::middleware(['api', 'auth:api', 'auto.authorize'])
    ->prefix('api/v1/admin/subscriptions')
    ->group(function () {
        Route::get('trashed', [SubscriptionTrashedController::class, 'index']);
        Route::patch('{id}/restore', [SubscriptionTrashedController::class, 'restore']);
        Route::delete('{id}/force-delete', [SubscriptionTrashedController::class, 'forceDelete']);

        Route::get('/', [SubscriptionController::class, 'index']);
        Route::get('{id}', [SubscriptionController::class, 'show']);
        Route::delete('{subscription}', [SubscriptionController::class, 'destroy']);
    });

Route::middleware(['api', 'auth:api', 'auto.authorize'])
    ->prefix('api/v1/admin/subscription-plans')
    ->group(function () {
        Route::get('trashed', [SubscriptionPlanTrashedController::class, 'index']);
        Route::patch('{id}/restore', [SubscriptionPlanTrashedController::class, 'restore']);
        Route::delete('{id}/force-delete', [SubscriptionPlanTrashedController::class, 'forceDelete']);

        Route::post('/', [SubscriptionPlanController::class, 'store']);
        Route::patch('{subscriptionPlan}', [SubscriptionPlanController::class, 'update']);
        Route::delete('{subscriptionPlan}', [SubscriptionPlanController::class, 'destroy']);

        Route::patch('{subscriptionPlan}/activate', [SubscriptionPlanStatusController::class, 'activate']);
        Route::patch('{subscriptionPlan}/deactivate', [SubscriptionPlanStatusController::class, 'deactivate']);
    });
