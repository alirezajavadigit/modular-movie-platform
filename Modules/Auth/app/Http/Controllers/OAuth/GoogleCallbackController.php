<?php

namespace Modules\Auth\Http\Controllers\OAuth;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Contracts\AuthServiceInterface;
use Modules\Auth\Http\Resources\Transformers\UserTransformer;
use Modules\Auth\Services\SocialAuth\GoogleAuthService;
use OpenApi\Attributes as OA;

class GoogleCallbackController extends Controller
{
    #[OA\Get(
        path: '/api/v1/auth/oauth/google/callback',
        operationId: 'auth.oauth.google.callback',
        summary: 'Handle the Google OAuth callback and issue a JWT',
        tags: ['Auth'],
        parameters: [
            new OA\Parameter(name: 'code', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'state', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/AuthSession')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function __invoke(AuthServiceInterface $auth, GoogleAuthService $googleAuth): JsonResponse
    {
        $socialUserDto = $googleAuth->resolveUser();

        $result = $auth->handleGoogleCallback($socialUserDto);

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $result['token'],
                'user' => fractal($result['user'], new UserTransformer())->toArray()['data'],
            ],
        ]);
    }
}
