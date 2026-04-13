<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Auth\Contracts\AuthServiceInterface;
use Modules\Auth\Contracts\OtpServiceInterface;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\Auth\DTOs\ChangePasswordDTO;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\DTOs\RegisterDTO;
use Modules\Auth\DTOs\SocialUserDTO;
use Modules\Auth\Models\User;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly OtpServiceInterface $otpService,
    ) {}

    public function register(RegisterDTO $dto): array
    {
        $user = $this->userRepository->create($dto);

        $this->otpService->dispatch($user, $dto->channel);

        $token = JWTAuth::fromUser($user);

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    public function login(LoginDTO $dto): array
    {
        $user = $this->userRepository->findByEmailOrPhone($dto->identifier);

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw new UnauthorizedHttpException('jwt-auth', __('auth-module::messages.invalid_credentials'));
        }

        $token = JWTAuth::fromUser($user);

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    public function logout(): void
    {
        auth('api')->logout();
    }

    public function refresh(): string
    {
        return auth('api')->refresh();
    }

    public function changePassword(User $user, ChangePasswordDTO $dto): void
    {
        if (! Hash::check($dto->currentPassword, $user->password)) {
            throw new UnauthorizedHttpException('jwt-auth', __('auth-module::messages.invalid_current_password'));
        }

        $this->userRepository->update($user, [
            'password' => $dto->newPassword,
        ]);
    }

    public function handleGoogleCallback(SocialUserDTO $dto): array
    {
        $user = $this->userRepository->findOrCreateFromSocial($dto);

        $token = JWTAuth::fromUser($user);

        return [
            'token' => $token,
            'user' => $user,
        ];
    }
}
