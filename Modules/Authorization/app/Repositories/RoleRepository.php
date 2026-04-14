<?php

namespace Modules\Authorization\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Authorization\Contracts\RoleRepositoryInterface;
use Modules\Authorization\DTOs\CreateRoleDTO;
use Modules\Authorization\DTOs\UpdateRoleDTO;
use Modules\Authorization\Models\Role;

class RoleRepository implements RoleRepositoryInterface
{
    public function __construct(
        private readonly Role $model,
    ) {}

    public function getAll(): Collection
    {
        return $this->model->all();
    }
    public function findById(int $id): ?Role
    {
        return $this->model->find($id);
    }
    public function findByName(string $name): ?Role
    {
        return $this->model->where("name", $name)->first();
    }
    public function findByNames(array $names): Collection
    {
        return $this->model->whereIn("name", $names)->get();
    }
    public function create(CreateRoleDTO $dto): Role
    {
        $role = $this->model->create([
            "name" => $dto->name,
            "guard_name" => $dto->guardName,
        ]);

        if (!empty($dto->permissions)) {
            $role->syncPermissions($dto->permissions);
        }

        return $role;
    }
    public function update(int $id, UpdateRoleDTO $dto): Role
    {
        $role = $this->model->findOrFail($id);
        $role->update(["name" => $dto->name]);

        if (!empty($dto->permissions)) {
            $role->syncPermissions($dto->permissions);
        }

        return $role->fresh();
    }
    public function hasUsersAssigned(int $id): bool
    {
        $role = $this->model->findOrFail($id);
        return $role->users()->exists();
    }
    public function delete(int $id): bool
    {
        $role = $this->model->findOrFail($id);
        return $role->delete();
    }
    public function syncPermissions(int $roleId, array $permissionNames): Role
    {
        $role = $this->model->findOrFail($roleId);
        $role->syncPermissions($permissionNames);
        return $role->fresh();
    }
}
