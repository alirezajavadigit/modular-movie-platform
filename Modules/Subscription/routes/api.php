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
    Route::get('/', [SubscriptionPlanQueryController::class, 'publicIndex']);
    Route::get('{subscriptionPlan}', [SubscriptionPlanQueryController::class, 'publicShow']);
});

Route::middleware(['api', 'auth:api'])->prefix('api/v1/subscriptions')->group(function () {
    Route::get('/', [SubscriptionQueryController::class, 'index']);
    Route::post('subscribe', [SubscriptionStatusController::class, 'subscribe']);
    Route::get('{subscription}', [SubscriptionQueryController::class, 'show']);
    Route::patch('{subscription}/cancel', [SubscriptionStatusController::class, 'cancel']);
});

Route::middleware(['api', 'auth:api'])
    ->prefix('api/v1/admin/subscriptions')
    ->group(function () {
        Route::get('trashed', [SubscriptionTrashedController::class, 'index']);

        Route::get('/', [SubscriptionController::class, 'index']);
        Route::get('{subscription}', [SubscriptionController::class, 'show']);

        Route::patch('{subscription}/activate', [SubscriptionStatusController::class, 'activate']);
        Route::patch('{subscription}/restore', [SubscriptionTrashedController::class, 'restore']);
        Route::delete('{subscription}/force-delete', [SubscriptionTrashedController::class, 'forceDelete']);

        Route::delete('{subscription}', [SubscriptionController::class, 'destroy']);
    });

Route::middleware(['api', 'auth:api'])
    ->prefix('api/v1/admin/subscription-plans')
    ->group(function () {
        Route::get('/', [SubscriptionPlanQueryController::class, 'index']);
        Route::get('trashed', [SubscriptionPlanTrashedController::class, 'index']);

        Route::post('/', [SubscriptionPlanController::class, 'store']);
        Route::put('{subscriptionPlan}', [SubscriptionPlanController::class, 'update']);

        Route::patch('{subscriptionPlan}/activate', [SubscriptionPlanStatusController::class, 'activate']);
        Route::patch('{subscriptionPlan}/deactivate', [SubscriptionPlanStatusController::class, 'deactivate']);
        Route::patch('{subscriptionPlan}/restore', [SubscriptionPlanTrashedController::class, 'restore']);
        Route::delete('{subscriptionPlan}/force-delete', [SubscriptionPlanTrashedController::class, 'forceDelete']);

        Route::delete('{subscriptionPlan}', [SubscriptionPlanController::class, 'destroy']);
    });
