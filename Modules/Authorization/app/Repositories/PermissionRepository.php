<?php

namespace Modules\Authorization\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Authorization\Contracts\PermissionRepositoryInterface;
use Modules\Authorization\Models\Permission;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function __construct(
        private readonly Permission $model,
    ) {}

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function findById(int $id): ?Permission
    {
        return $this->model->find($id);
    }

    public function findByName(string $name): ?Permission
    {
        return $this->model->where('name', $name)->first();
    }

    public function findByNames(array $names): Collection
    {
        return $this->model->whereIn('name', $names)->get();
    }

    public function findByModule(string $modulePrefix): Collection
    {
        return $this->model
            ->where('name', 'like', $modulePrefix . '.%')
            ->get();
    }
}
