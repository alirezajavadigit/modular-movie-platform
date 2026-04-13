<?php

namespace Modules\Auth\Repositories;

use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\Auth\DTOs\RegisterDTO;
use Modules\Auth\DTOs\SocialUserDTO;
use Modules\Auth\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly User $model,
    ) {}

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function findByPhone(string $phone): ?User
    {
        return $this->model->where('phone', $phone)->first();
    }

    public function findByEmailOrPhone(string $identifier): ?User
    {
        return $this->model
            ->where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();
    }

    public function create(RegisterDTO $dto): User
    {
        return $this->model->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'password' => $dto->password,
        ]);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->refresh();
    }

    public function findOrCreateFromSocial(SocialUserDTO $dto): User
    {
        $user = $this->findByEmail($dto->email);

        if (! $user) {
            $user = $this->model->create([
                'name' => $dto->name ?? '',
                'email' => $dto->email,
                'email_verified_at' => now(),
            ]);
        }

        $user->oauthProviders()->updateOrCreate(
            [
                'provider' => $dto->provider,
                'provider_user_id' => $dto->providerId,
            ],
            [
                'access_token' => $dto->token,
                'refresh_token' => $dto->refreshToken,
                'token_expires_at' => $dto->tokenExpiresAt,
            ],
        );

        return $user;
    }
}
