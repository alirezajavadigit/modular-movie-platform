<?php

namespace Modules\Movie\Policies;

use Modules\Auth\Models\User;
use Modules\Movie\Models\Episode;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class EpisodePolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('episodes.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user, Episode $episode): bool
    {
        try {
            return $user->hasPermissionTo('episodes.view');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('episodes.create');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function update(User $user, Episode $episode): bool
    {
        try {
            return $user->hasPermissionTo('episodes.update');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function delete(User $user, Episode $episode): bool
    {
        try {
            return $user->hasPermissionTo('episodes.delete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function restore(User $user): bool
    {
        try {
            return $user->hasPermissionTo('episodes.restore');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
