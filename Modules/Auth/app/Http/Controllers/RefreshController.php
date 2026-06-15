<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Contracts\AuthServiceInterface;
use OpenApi\Attributes as OA;

class RefreshController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/refresh',
        operationId: 'auth.refresh',
        summary: 'Exchange the current JWT for a fresh one',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/AuthToken')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 429, ref: '#/components/responses/TooManyRequests')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function __invoke(Request $request, AuthServiceInterface $auth): JsonResponse
    {
        $token = $auth->refresh();

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token,
            ],
        ]);
    }
}
