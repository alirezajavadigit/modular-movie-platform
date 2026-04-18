<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Contracts\OtpServiceInterface;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\Auth\DTOs\ForgotPasswordDTO;
use Modules\Auth\Http\Requests\ForgotPasswordRequest;

class ForgotPasswordController extends Controller
{
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
