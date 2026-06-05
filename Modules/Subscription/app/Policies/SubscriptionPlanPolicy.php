<?php

namespace Modules\Subscription\Policies;

use Modules\Auth\Models\User;
use Modules\Subscription\Models\SubscriptionPlan;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class SubscriptionPlanPolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('subscription_plans.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user, SubscriptionPlan $plan): bool
    {
        try {
            return $user->hasPermissionTo('subscription_plans.view');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('subscription_plans.create');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function update(User $user, SubscriptionPlan $plan): bool
    {
        try {
            return $user->hasPermissionTo('subscription_plans.update');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function delete(User $user, SubscriptionPlan $plan): bool
    {
        try {
            return $user->hasPermissionTo('subscription_plans.delete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function restore(User $user, SubscriptionPlan $plan): bool
    {
        try {
            return $user->hasPermissionTo('subscription_plans.restore');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function forceDelete(User $user, SubscriptionPlan $plan): bool
    {
        try {
            return $user->hasPermissionTo('subscription_plans.forceDelete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function viewTrashed(User $user): bool
    {
        try {
            return $user->hasPermissionTo('subscription_plans.viewTrashed');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function activate(User $user, SubscriptionPlan $plan): bool
    {
        try {
            return $user->hasPermissionTo('subscription_plans.activate');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function deactivate(User $user, SubscriptionPlan $plan): bool
    {
        try {
            return $user->hasPermissionTo('subscription_plans.deactivate');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
