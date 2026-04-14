<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ApiResponseService
{
    public function success(mixed $data = null, string $message = 'Operation successful', int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public function created(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->success(
            data: $data,
            message: $message,
            statusCode: Response::HTTP_CREATED,
        );
    }

    public function noContent(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => null,
        ], Response::HTTP_OK);
    }

    public function error(string $message = 'Something went wrong', int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    public function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error(
            message: $message,
            statusCode: Response::HTTP_NOT_FOUND,
        );
    }

    public function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error(
            message: $message,
            statusCode: Response::HTTP_UNAUTHORIZED,
        );
    }

    public function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error(
            message: $message,
            statusCode: Response::HTTP_FORBIDDEN,
        );
    }

    public function validationError(mixed $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error(
            message: $message,
            statusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
            errors: $errors,
        );
    }

    public function fractal(mixed $data, mixed $transformer, string $message = 'Operation successful', int $statusCode = Response::HTTP_OK): JsonResponse
    {
        $transformed = fractal($data, $transformer)->toArray();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $transformed['data'],
        ], $statusCode);
    }

    public function fractalCreated(mixed $data, mixed $transformer, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->fractal(
            data: $data,
            transformer: $transformer,
            message: $message,
            statusCode: Response::HTTP_CREATED,
        );
    }

    public function paginated(mixed $data, mixed $transformer, string $message = 'Operation successful'): JsonResponse
    {
        $result = fractal($data, $transformer)->toArray();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $result['data'],
            'meta' => $result['meta'] ?? null,
            'pagination' => $result['pagination'] ?? null,
        ], Response::HTTP_OK);
    }
}
