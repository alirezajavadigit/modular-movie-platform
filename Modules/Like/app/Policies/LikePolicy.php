<?php

namespace Modules\Like\Policies;

use Modules\Auth\Models\User;
use Modules\Like\Models\Like;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class LikePolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('likes.viewAny');
        } catch (PermissionDoesNotExist) {
            return true;
        }
    }

    public function view(User $user, Like $like): bool
    {
        try {
            return $user->hasPermissionTo('likes.view') || $user->id === $like->user_id;
        } catch (PermissionDoesNotExist) {
            return $user->id === $like->user_id;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('likes.create');
        } catch (PermissionDoesNotExist) {
            return true;
        }
    }

    public function delete(User $user, Like $like): bool
    {
        try {
            return $user->hasPermissionTo('likes.delete') || $user->id === $like->user_id;
        } catch (PermissionDoesNotExist) {
            return $user->id === $like->user_id;
        }
    }

    public function restore(User $user, Like $like): bool
    {
        try {
            return $user->hasPermissionTo('likes.restore');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
