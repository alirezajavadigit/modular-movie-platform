<?php

namespace Modules\Favorite\Policies;

use Modules\Auth\Models\User;
use Modules\Favorite\Models\Favorite;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class FavoritePolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('favorites.viewAny');
        } catch (PermissionDoesNotExist) {
            return true;
        }
    }

    public function view(User $user, Favorite $favorite): bool
    {
        try {
            return $user->hasPermissionTo('favorites.view') || $user->id === $favorite->user_id;
        } catch (PermissionDoesNotExist) {
            return $user->id === $favorite->user_id;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('favorites.create');
        } catch (PermissionDoesNotExist) {
            return true;
        }
    }

    public function delete(User $user, Favorite $favorite): bool
    {
        try {
            return $user->hasPermissionTo('favorites.delete') || $user->id === $favorite->user_id;
        } catch (PermissionDoesNotExist) {
            return $user->id === $favorite->user_id;
        }
    }

    public function restore(User $user, Favorite $favorite): bool
    {
        try {
            return $user->hasPermissionTo('favorites.restore');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
