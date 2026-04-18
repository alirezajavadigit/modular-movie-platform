<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Contracts\AuthServiceInterface;
use Modules\Auth\DTOs\ChangePasswordDTO;
use Modules\Auth\Http\Requests\ChangePasswordRequest;

class ChangePasswordController extends Controller
{
    public function __invoke(ChangePasswordRequest $request, AuthServiceInterface $auth): JsonResponse
    {
        $dto = new ChangePasswordDTO(
            currentPassword: $request->validated('current_password'),
            newPassword: $request->validated('new_password'),
        );

        $auth->changePassword($request->user(), $dto);

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => __('auth-module::messages.password_changed'),
            ],
        ]);
    }
}
