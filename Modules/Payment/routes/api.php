<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentController;
use Modules\Payment\Http\Controllers\PaymentQueryController;
use Modules\Payment\Http\Controllers\PaymentStatusController;
use Modules\Payment\Http\Controllers\PaymentTrashedController;

Route::middleware(['api', 'auth:api'])->prefix('api/v1/payments')->group(function () {
    Route::get('/', [PaymentQueryController::class, 'index']);
    Route::get('{payment}', [PaymentQueryController::class, 'show']);
    Route::patch('{payment}/verify', [PaymentStatusController::class, 'verify']);
});

Route::middleware(['api', 'auth:api', 'auto.authorize'])
    ->prefix('api/v1/admin/payments')
    ->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::get('{payment}', [PaymentController::class, 'show']);
        Route::delete('{payment}', [PaymentController::class, 'destroy']);

        Route::get('trashed', [PaymentTrashedController::class, 'index']);
        Route::patch('{payment}/restore', [PaymentTrashedController::class, 'restore']);
        Route::delete('{payment}/force-delete', [PaymentTrashedController::class, 'forceDelete']);
    });
