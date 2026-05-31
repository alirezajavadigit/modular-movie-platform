<?php

namespace Modules\User\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\User;
use Modules\User\DTOs\CreateUserDTO;
use Modules\User\DTOs\UpdateUserDTO;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByPhone(string $phone): ?User;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function search(string $term, int $perPage = 15): LengthAwarePaginator;

    public function create(CreateUserDTO $dto): User;

    public function update(int $id, UpdateUserDTO $dto): User;

    public function delete(int $id): bool;

    public function forceDelete(int $id): bool;

    public function restore(int $id): User;

    public function getTrashed(int $perPage = 15): LengthAwarePaginator;

    public function syncRoles(int $id, array $roles): User;

    public function exists(int $id): bool;
}
