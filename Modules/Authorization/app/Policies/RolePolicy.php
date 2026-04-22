<?php

namespace Modules\Authorization\Policies;

use Modules\Auth\Models\User;
use Modules\Authorization\Models\Role;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('roles.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user, Role $role): bool
    {
        try {
            return $user->hasPermissionTo('roles.view');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('roles.create');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function update(User $user, Role $role): bool
    {
        try {
            return $user->hasPermissionTo('roles.update');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function delete(User $user, Role $role): bool
    {
        try {
            return $user->hasPermissionTo('roles.delete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function syncPermissions(User $user, Role $role): bool
    {
        try {
            return $user->hasPermissionTo('roles.syncPermissions');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function assignToUser(User $user): bool
    {
        try {
            return $user->hasPermissionTo('roles.assignToUser');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function revokeFromUser(User $user): bool
    {
        try {
            return $user->hasPermissionTo('roles.revokeFromUser');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
