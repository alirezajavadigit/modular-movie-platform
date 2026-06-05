<?php

namespace Modules\Authorization\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Authorization\Contracts\RoleRepositoryInterface;
use Modules\Authorization\Contracts\RoleServiceInterface;
use Modules\Authorization\DTOs\CreateRoleDTO;
use Modules\Authorization\DTOs\UpdateRoleDTO;
use Modules\Authorization\Models\Role;
use Spatie\Permission\Exceptions\RoleAlreadyExists;

class RoleService implements RoleServiceInterface

{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}
    public function getAllRoles(): Collection
    {
        return $this->roleRepository->getAll();
    }
    public function getRoleById(int $id): Role
    {
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            throw new ModelNotFoundException("Role with ID {$id} not found.");
        }

        return $role;
    }
    public function createRole(CreateRoleDTO $dto): Role
    {
        $role = $this->roleRepository->findByName($dto->name);
        if ($role) {
            throw RoleAlreadyExists::create($dto->name, $dto->guardName);
        }
        return $this->roleRepository->create($dto);
    }
    public function updateRole(int $id, UpdateRoleDTO $dto): Role
    {
        $this->getRoleById($id);
        return $this->roleRepository->update($id, $dto);
    }
    public function deleteRole(int $id): bool
    {
        $role = $this->getRoleById($id);

        if ($this->roleRepository->hasUsersAssigned($role->id)) {
            throw new \DomainException("Cannot delete role '{$role->name}' while it is assigned to active users.");
        }
        return $this->roleRepository->delete($id);
    }
    public function syncPermissionsToRole(int $roleId, array $permissionNames): Role
    {
        $this->getRoleById($roleId);
        return $this->roleRepository->syncPermissions($roleId, $permissionNames);
    }
}
