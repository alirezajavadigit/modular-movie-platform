<?php

namespace Modules\Notification\Policies;

use Modules\Auth\Models\User;
use Modules\Notification\Models\Notification;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('notifications.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user, Notification $notification): bool
    {
        try {
            return $user->hasPermissionTo('notifications.view');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('notifications.create');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function update(User $user, Notification $notification): bool
    {
        try {
            return $user->hasPermissionTo('notifications.update');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function delete(User $user, Notification $notification): bool
    {
        try {
            return $user->hasPermissionTo('notifications.delete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function restore(User $user, Notification $notification): bool
    {
        try {
            return $user->hasPermissionTo('notifications.restore');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function forceDelete(User $user, Notification $notification): bool
    {
        try {
            return $user->hasPermissionTo('notifications.forceDelete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function viewTrashed(User $user): bool
    {
        try {
            return $user->hasPermissionTo('notifications.viewTrashed');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function markRead(User $user, Notification $notification): bool
    {
        try {
            return $user->hasPermissionTo('notifications.markRead');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
