<?php

namespace Modules\Discussion\Policies;

use Modules\Auth\Models\User;
use Modules\Discussion\Models\Discussion;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class DiscussionPolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('discussions.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user, Discussion $discussion): bool
    {
        try {
            return $user->hasPermissionTo('discussions.view');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('discussions.create');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function update(User $user, Discussion $discussion): bool
    {
        try {
            return $user->hasPermissionTo('discussions.update');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function delete(User $user, Discussion $discussion): bool
    {
        try {
            return $user->hasPermissionTo('discussions.delete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function restore(User $user): bool
    {
        try {
            return $user->hasPermissionTo('discussions.restore');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function forceDelete(User $user, Discussion $discussion): bool
    {
        try {
            return $user->hasPermissionTo('discussions.forceDelete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function approve(User $user, Discussion $discussion): bool
    {
        try {
            return $user->hasPermissionTo('discussions.approve');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function reject(User $user, Discussion $discussion): bool
    {
        try {
            return $user->hasPermissionTo('discussions.reject');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function markAsPending(User $user, Discussion $discussion): bool
    {
        try {
            return $user->hasPermissionTo('discussions.markAsPending');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function viewPending(User $user): bool
    {
        try {
            return $user->hasPermissionTo('discussions.viewPending');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
