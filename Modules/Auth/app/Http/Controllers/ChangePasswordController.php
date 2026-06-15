<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Contracts\AuthServiceInterface;
use Modules\Auth\DTOs\ChangePasswordDTO;
use Modules\Auth\Http\Requests\ChangePasswordRequest;
use OpenApi\Attributes as OA;

class ChangePasswordController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/change-password',
        operationId: 'auth.change-password',
        summary: 'Change the authenticated user password',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/ChangePasswordRequest'),
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/AuthMessage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 422, ref: '#/components/responses/LegacyValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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
