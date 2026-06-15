<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Contracts\AuthServiceInterface;
use Modules\Auth\DTOs\RegisterDTO;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Resources\Transformers\UserTransformer;
use OpenApi\Attributes as OA;

class RegisterController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/register',
        operationId: 'auth.register',
        summary: 'Register a new account',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/RegisterRequest'),
    )]
    #[OA\Response(response: 201, ref: '#/components/responses/AuthSessionCreated')]
    #[OA\Response(response: 422, ref: '#/components/responses/LegacyValidationError')]
    #[OA\Response(response: 429, ref: '#/components/responses/TooManyRequests')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function __invoke(RegisterRequest $request, AuthServiceInterface $auth): JsonResponse
    {
        $dto = new RegisterDTO(
            name: $request->validated('name'),
            email: $request->validated('email'),
            phone: $request->validated('phone'),
            password: $request->validated('password'),
            channel: $request->validated('email') ? 'email' : 'phone',
        );

        $result = $auth->register($dto);

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $result['token'],
                'user' => fractal($result['user'], new UserTransformer())->toArray()['data'],
            ],
        ], 201);
    }
}
