<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Contracts\AuthServiceInterface;
use OpenApi\Attributes as OA;

class LogoutController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/logout',
        operationId: 'auth.logout',
        summary: 'Invalidate the current JWT',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/AuthMessage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function __invoke(Request $request, AuthServiceInterface $auth): JsonResponse
    {
        $auth->logout();

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => __('auth-module::messages.logged_out'),
            ],
        ]);
    }
}
