<?php

namespace Modules\Auth\Contracts;

use Modules\Auth\DTOs\ChangePasswordDTO;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\DTOs\RegisterDTO;
use Modules\Auth\DTOs\SocialUserDTO;
use Modules\Auth\Models\User;

interface AuthServiceInterface
{
    public function register(RegisterDTO $dto): array;

    public function login(LoginDTO $dto): array;

    public function logout(): void;

    public function refresh(): string;

    public function changePassword(User $user, ChangePasswordDTO $dto): void;

    public function handleGoogleCallback(SocialUserDTO $dto): array;
}
