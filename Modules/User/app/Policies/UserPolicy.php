<?php

namespace Modules\User\Policies;

use Modules\Auth\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('users.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user, User $model): bool
    {
        try {
            if ($user->hasPermissionTo('users.view')) {
                return true;
            }

            return $user->id === $model->id && $user->hasPermissionTo('users.viewOwn');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('users.create');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function update(User $user, User $model): bool
    {
        try {
            if ($user->hasPermissionTo('users.update')) {
                return true;
            }

            return $user->id === $model->id && $user->hasPermissionTo('users.updateOwn');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function delete(User $user, User $model): bool
    {
        try {
            return $user->hasPermissionTo('users.delete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function restore(User $user, User $model): bool
    {
        try {
            return $user->hasPermissionTo('users.restore');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function forceDelete(User $user, User $model): bool
    {
        try {
            return $user->hasPermissionTo('users.forceDelete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function viewTrashed(User $user): bool
    {
        try {
            return $user->hasPermissionTo('users.viewTrashed');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
