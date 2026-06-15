<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Contracts\OtpServiceInterface;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\Auth\DTOs\ForgotPasswordDTO;
use Modules\Auth\Http\Requests\ForgotPasswordRequest;
use OpenApi\Attributes as OA;

class ForgotPasswordController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/forgot-password',
        operationId: 'auth.forgot-password',
        summary: 'Dispatch a password reset OTP via email or SMS',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/ForgotPasswordRequest'),
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/AuthMessage')]
    #[OA\Response(response: 422, ref: '#/components/responses/LegacyValidationError')]
    #[OA\Response(response: 429, ref: '#/components/responses/TooManyRequests')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function __invoke(
        ForgotPasswordRequest $request,
        OtpServiceInterface $otp,
        UserRepositoryInterface $userRepository,
    ): JsonResponse {
        $dto = new ForgotPasswordDTO(
            identifier: $request->validated('identifier'),
        );

        $user = $userRepository->findByEmailOrPhone($dto->identifier);

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => __('auth-module::messages.user_not_found'),
            ], 422);
        }

        $channel = $user->email === $dto->identifier ? 'email' : 'sms';

        $otp->dispatch($user, $channel);

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => __('auth-module::messages.otp_sent'),
            ],
        ]);
    }
}
