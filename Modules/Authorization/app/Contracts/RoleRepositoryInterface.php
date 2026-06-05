<?php

namespace Modules\Authorization\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Authorization\DTOs\CreateRoleDTO;
use Modules\Authorization\DTOs\UpdateRoleDTO;
use Modules\Authorization\Models\Role;

interface RoleRepositoryInterface
{
    public function getAll(): Collection;
    public function findById(int $id): ?Role;
    public function findByName(string $name): ?Role;
    public function findByNames(array $names): Collection;
    public function create(CreateRoleDTO $dto): Role;
    public function update(int $id, UpdateRoleDTO $dto): Role;
    public function hasUsersAssigned(int $id): bool;
    public function delete(int $id): bool;
    public function syncPermissions(int $roleId, array $permissionNames): Role;
}
