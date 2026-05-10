<?php

namespace Modules\Subscription\Policies;

use Modules\Auth\Models\User;
use Modules\Subscription\Models\Subscription;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('subscriptions.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user, Subscription $subscription): bool
    {
        try {
            if ($user->hasPermissionTo('subscriptions.view')) {
                return true;
            }

            return $user->id === $subscription->user_id
                && $user->hasPermissionTo('subscriptions.viewOwn');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('subscriptions.create');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        try {
            if ($user->hasPermissionTo('subscriptions.delete')) {
                return true;
            }

            return $user->id === $subscription->user_id
                && $user->hasPermissionTo('subscriptions.deleteOwn');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function restore(User $user, Subscription $subscription): bool
    {
        try {
            return $user->hasPermissionTo('subscriptions.restore');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function forceDelete(User $user, Subscription $subscription): bool
    {
        try {
            return $user->hasPermissionTo('subscriptions.forceDelete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function viewTrashed(User $user): bool
    {
        try {
            return $user->hasPermissionTo('subscriptions.viewTrashed');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function cancel(User $user, Subscription $subscription): bool
    {
        try {
            if ($user->hasPermissionTo('subscriptions.cancel')) {
                return true;
            }

            return $user->id === $subscription->user_id;
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function activate(User $user, Subscription $subscription): bool
    {
        try {
            return $user->hasPermissionTo('subscriptions.activate');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
