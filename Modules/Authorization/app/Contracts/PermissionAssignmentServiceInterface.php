<?php

namespace Modules\Authorization\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Auth\Models\User;
use Modules\Authorization\DTOs\AssignPermissionDTO;
use Modules\Authorization\DTOs\RevokePermissionDTO;

interface PermissionAssignmentServiceInterface
{
    public function givePermissions(AssignPermissionDTO $dto): User;
    public function revokePermissions(RevokePermissionDTO $dto): User;
    public function getUserPermissions(int $userId): Collection;
}
