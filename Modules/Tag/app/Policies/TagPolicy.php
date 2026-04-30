<?php

namespace Modules\Tag\Policies;

use Modules\Auth\Models\User;
use Modules\Tag\Models\Tag;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('tags.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user, Tag $tag): bool
    {
        try {
            return $user->hasPermissionTo('tags.view');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('tags.create');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function update(User $user, Tag $tag): bool
    {
        try {
            return $user->hasPermissionTo('tags.update');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function delete(User $user, Tag $tag): bool
    {
        try {
            return $user->hasPermissionTo('tags.delete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function restore(User $user, Tag $tag): bool
    {
        try {
            return $user->hasPermissionTo('tags.restore');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function forceDelete(User $user, Tag $tag): bool
    {
        try {
            return $user->hasPermissionTo('tags.forceDelete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function activate(User $user, Tag $tag): bool
    {
        try {
            return $user->hasPermissionTo('tags.activate');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function deactivate(User $user, Tag $tag): bool
    {
        try {
            return $user->hasPermissionTo('tags.deactivate');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function viewTrashed(User $user): bool
    {
        try {
            return $user->hasPermissionTo('tags.viewTrashed');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
