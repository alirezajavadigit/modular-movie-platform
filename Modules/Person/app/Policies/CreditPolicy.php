<?php

namespace Modules\Person\Policies;

use Modules\Auth\Models\User;
use Modules\Person\Models\Credit;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class CreditPolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('credits.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user, Credit $credit): bool
    {
        try {
            return $user->hasPermissionTo('credits.view');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('credits.create');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function update(User $user, Credit $credit): bool
    {
        try {
            return $user->hasPermissionTo('credits.update');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function delete(User $user, Credit $credit): bool
    {
        try {
            return $user->hasPermissionTo('credits.delete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function restore(User $user, Credit $credit): bool
    {
        try {
            return $user->hasPermissionTo('credits.restore');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function forceDelete(User $user, Credit $credit): bool
    {
        try {
            return $user->hasPermissionTo('credits.forceDelete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
