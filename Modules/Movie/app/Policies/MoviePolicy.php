<?php

namespace Modules\Movie\Policies;

use Modules\Auth\Models\User;
use Modules\Movie\Models\Movie;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class MoviePolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('movies.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user, Movie $movie): bool
    {
        try {
            return $user->hasPermissionTo('movies.view');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('movies.create');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function update(User $user, Movie $movie): bool
    {
        try {
            return $user->hasPermissionTo('movies.update');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function delete(User $user, Movie $movie): bool
    {
        try {
            return $user->hasPermissionTo('movies.delete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function restore(User $user): bool
    {
        try {
            return $user->hasPermissionTo('movies.restore');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
