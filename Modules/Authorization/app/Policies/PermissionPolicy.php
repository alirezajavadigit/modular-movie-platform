<?php

namespace Modules\Authorization\Policies;

use Modules\Auth\Models\User;
use Modules\Authorization\Models\Permission;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('permissions.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user, Permission $permission): bool
    {
        try {
            return $user->hasPermissionTo('permissions.view');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function assignToUser(User $user): bool
    {
        try {
            return $user->hasPermissionTo('permissions.assignToUser');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function revokeFromUser(User $user): bool
    {
        try {
            return $user->hasPermissionTo('permissions.revokeFromUser');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
