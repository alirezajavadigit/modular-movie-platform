<?php

namespace Modules\Auth\Contracts;

use Modules\Auth\DTOs\RegisterDTO;
use Modules\Auth\DTOs\SocialUserDTO;
use Modules\Auth\Models\User;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findById(int $id): ?User;

    public function findByPhone(string $phone): ?User;

    public function findByEmailOrPhone(string $identifier): ?User;

    public function create(RegisterDTO $dto): User;

    public function update(User $user, array $data): User;

    public function findOrCreateFromSocial(SocialUserDTO $dto): User;
}
