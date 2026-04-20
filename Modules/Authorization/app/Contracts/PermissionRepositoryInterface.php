<?php

namespace Modules\Authorization\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Authorization\Models\Permission;

interface PermissionRepositoryInterface
{
    public function getAll(): Collection;
    public function findById(int $id): ?Permission;
    public function findByName(string $name): ?Permission;
    public function findByNames(array $names): Collection;
    public function findByModule(string $modulePrefix): Collection;
}
