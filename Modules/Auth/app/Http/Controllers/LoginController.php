<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Contracts\AuthServiceInterface;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Resources\Transformers\UserTransformer;
use OpenApi\Attributes as OA;

class LoginController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/login',
        operationId: 'auth.login',
        summary: 'Authenticate with email or phone and obtain a JWT',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/LoginRequest'),
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/AuthSession')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 422, ref: '#/components/responses/LegacyValidationError')]
    #[OA\Response(response: 429, ref: '#/components/responses/TooManyRequests')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function __invoke(LoginRequest $request, AuthServiceInterface $auth): JsonResponse
    {
        $dto = new LoginDTO(
            identifier: $request->validated('identifier'),
            password: $request->validated('password'),
        );

        $result = $auth->login($dto);

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $result['token'],
                'user' => fractal($result['user'], new UserTransformer())->toArray()['data'],
            ],
        ]);
    }
}
