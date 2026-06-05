<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Contracts\AuthServiceInterface;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Resources\Transformers\UserTransformer;

class LoginController extends Controller
{
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
