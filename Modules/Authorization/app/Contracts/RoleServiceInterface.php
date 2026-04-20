<?php

namespace Modules\Authorization\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Authorization\DTOs\CreateRoleDTO;
use Modules\Authorization\DTOs\UpdateRoleDTO;
use Modules\Authorization\Models\Role;

interface RoleServiceInterface
{
    public function getAllRoles(): Collection;
    public function getRoleById(int $id): Role;
    public function createRole(CreateRoleDTO $dto): Role;
    public function updateRole(int $id, UpdateRoleDTO $dto): Role;
    public function deleteRole(int $id): bool;
    public function syncPermissionsToRole(int $roleId, array $permissionNames): Role;
}
