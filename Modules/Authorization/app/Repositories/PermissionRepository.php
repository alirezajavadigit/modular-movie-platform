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
        return $this->model->newQuery()->get();
    }

    public function findById(int $id): ?Permission
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByName(string $name): ?Permission
    {
        return $this->model->newQuery()->where('name', $name)->first();
    }

    public function findByNames(array $names): Collection
    {
        return $this->model->newQuery()->whereIn('name', $names)->get();
    }

    public function findByModule(string $modulePrefix): Collection
    {
        return $this->model->newQuery()
            ->where('name', 'like', $modulePrefix . '.%')
            ->get();
    }
}
