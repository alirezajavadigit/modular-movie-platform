<?php

namespace Modules\Person\Policies;

use Modules\Auth\Models\User;
use Modules\Person\Models\Person;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class PersonPolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('persons.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user, Person $person): bool
    {
        try {
            return $user->hasPermissionTo('persons.view');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('persons.create');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function update(User $user, Person $person): bool
    {
        try {
            return $user->hasPermissionTo('persons.update');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function delete(User $user, Person $person): bool
    {
        try {
            return $user->hasPermissionTo('persons.delete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function restore(User $user, Person $person): bool
    {
        try {
            return $user->hasPermissionTo('persons.restore');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function forceDelete(User $user, Person $person): bool
    {
        try {
            return $user->hasPermissionTo('persons.forceDelete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function activate(User $user, Person $person): bool
    {
        try {
            return $user->hasPermissionTo('persons.activate');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function deactivate(User $user, Person $person): bool
    {
        try {
            return $user->hasPermissionTo('persons.deactivate');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function viewTrashed(User $user): bool
    {
        try {
            return $user->hasPermissionTo('persons.viewTrashed');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
