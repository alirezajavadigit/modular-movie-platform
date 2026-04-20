<?php

namespace Modules\Authorization\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Auth\Models\User;
use Modules\Authorization\DTOs\AssignRoleDTO;
use Modules\Authorization\DTOs\RevokeRoleDTO;
use Modules\Authorization\DTOs\SyncRoleDTO;

interface RoleAssignmentServiceInterface
{
    public function assignRoles(AssignRoleDTO $dto): User;
    public function revokeRoles(RevokeRoleDTO $dto): User;
    public function syncRoles(SyncRoleDTO $dto): User;
    public function getUserRoles(int $userId): Collection;
}
