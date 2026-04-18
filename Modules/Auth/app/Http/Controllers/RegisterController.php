<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Contracts\AuthServiceInterface;
use Modules\Auth\DTOs\RegisterDTO;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Resources\Transformers\UserTransformer;

class RegisterController extends Controller
{
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
