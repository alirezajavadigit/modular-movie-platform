<?php

namespace Modules\Payment\Policies;

use Modules\Auth\Models\User;
use Modules\Payment\Models\Payment;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('payments.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user, Payment $payment): bool
    {
        try {
            if ($user->hasPermissionTo('payments.view')) {
                return true;
            }

            return $user->id === $payment->user_id
                && $user->hasPermissionTo('payments.viewOwn');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('payments.create');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function delete(User $user, Payment $payment): bool
    {
        try {
            if ($user->hasPermissionTo('payments.delete')) {
                return true;
            }

            return $user->id === $payment->user_id
                && $user->hasPermissionTo('payments.deleteOwn');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function restore(User $user, Payment $payment): bool
    {
        try {
            return $user->hasPermissionTo('payments.restore');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        try {
            return $user->hasPermissionTo('payments.forceDelete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function viewTrashed(User $user): bool
    {
        try {
            return $user->hasPermissionTo('payments.viewTrashed');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function verify(User $user, Payment $payment): bool
    {
        try {
            if ($user->hasPermissionTo('payments.verify')) {
                return true;
            }

            return $user->id === $payment->user_id;
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
