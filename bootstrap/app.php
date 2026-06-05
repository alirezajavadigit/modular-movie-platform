<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use App\Facades\ApiResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::validationError(
                    errors: $e->errors(),
                );
            }
        });

        $exceptions->render(function (DomainException $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    message: $e->getMessage(),
                    statusCode: 422,
                );
            }
        });

        $exceptions->render(function (\TypeError $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    message: 'Invalid parameter type.',
                    statusCode: 422,
                );
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::unauthorized();
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::forbidden(
                    message: $e->getMessage() ?: 'You do not have permission to perform this action.',
                );
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson()) {
                $model = class_basename($e->getModel());
                return ApiResponse::notFound(
                    message: "{$model} not found.",
                );
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::notFound(
                    message: 'The requested endpoint does not exist.',
                );
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    message: 'HTTP method not allowed.',
                    statusCode: 405,
                );
            }
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    message: 'Too many requests. Please slow down.',
                    statusCode: 429,
                    errors: ['retry_after' => $e->getHeaders()['Retry-After'] ?? null],
                );
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    message: $e->getMessage() ?: 'An HTTP error occurred.',
                    statusCode: $e->getStatusCode(),
                );
            }
        });

        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    message: app()->isProduction()
                        ? 'A database error occurred.'
                        : $e->getMessage(),
                );
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    message: app()->isProduction()
                        ? 'An unexpected error occurred.'
                        : $e->getMessage(),
                    errors: app()->isProduction() ? null : [
                        'type' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ],
                );
            }
        });
    })
    ->create();
