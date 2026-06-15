<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Http\Resources\Transformers\UserTransformer;
use OpenApi\Attributes as OA;

class MeController extends Controller
{
    #[OA\Get(
        path: '/api/v1/auth/me',
        operationId: 'auth.me',
        summary: 'Get the authenticated user profile',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/AuthProfile')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => fractal($request->user(), new UserTransformer())->toArray()['data'],
        ]);
    }
}
