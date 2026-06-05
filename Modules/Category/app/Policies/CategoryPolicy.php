<?php

namespace Modules\Category\Policies;

use Modules\Auth\Models\User;
use Modules\Category\Models\Category;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('categories.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user, Category $category): bool
    {
        try {
            return $user->hasPermissionTo('categories.view');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('categories.create');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function update(User $user, Category $category): bool
    {
        try {
            return $user->hasPermissionTo('categories.update');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function delete(User $user, Category $category): bool
    {
        try {
            return $user->hasPermissionTo('categories.delete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function restore(User $user, Category $category): bool
    {
        try {
            return $user->hasPermissionTo('categories.restore');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function forceDelete(User $user, Category $category): bool
    {
        try {
            return $user->hasPermissionTo('categories.forceDelete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function activate(User $user, Category $category): bool
    {
        try {
            return $user->hasPermissionTo('categories.activate');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function deactivate(User $user, Category $category): bool
    {
        try {
            return $user->hasPermissionTo('categories.deactivate');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function viewTrashed(User $user): bool
    {
        try {
            return $user->hasPermissionTo('categories.viewTrashed');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
