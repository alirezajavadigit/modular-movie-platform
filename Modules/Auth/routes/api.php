<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\ChangePasswordController;
use Modules\Auth\Http\Controllers\ForgotPasswordController;
use Modules\Auth\Http\Controllers\LoginController;
use Modules\Auth\Http\Controllers\LogoutController;
use Modules\Auth\Http\Controllers\MeController;
use Modules\Auth\Http\Controllers\OAuth\GoogleCallbackController;
use Modules\Auth\Http\Controllers\OAuth\GoogleRedirectController;
use Modules\Auth\Http\Controllers\RefreshController;
use Modules\Auth\Http\Controllers\RegisterController;

Route::prefix('v1/auth')->group(function () {
    Route::post('register', RegisterController::class)
        ->middleware('throttle:auth.register')
        ->name('auth.register');

    Route::post('login', LoginController::class)
        ->middleware('throttle:auth.login')
        ->name('auth.login');

    Route::post('forgot-password', ForgotPasswordController::class)
        ->middleware('throttle:auth.forgot-password')
        ->name('auth.forgot-password');

    Route::get('oauth/google', GoogleRedirectController::class)
        ->name('auth.oauth.google.redirect');

    Route::get('oauth/google/callback', GoogleCallbackController::class)
        ->name('auth.oauth.google.callback');

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', LogoutController::class)
            ->name('auth.logout');

        Route::get('me', MeController::class)
            ->name('auth.me');

        Route::post('refresh', RefreshController::class)
            ->middleware('throttle:auth.refresh')
            ->name('auth.refresh');

        Route::post('change-password', ChangePasswordController::class)
            ->name('auth.change-password');
    });
});
