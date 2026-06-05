<?php

namespace Modules\Subscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SubscriptionPermissionSeeder extends Seeder
{
    private const GUARD = 'api';

    private const PERMISSIONS = [
        'subscriptions.viewAny',
        'subscriptions.view',
        'subscriptions.viewOwn',
        'subscriptions.create',
        'subscriptions.delete',
        'subscriptions.deleteOwn',
        'subscriptions.restore',
        'subscriptions.forceDelete',
        'subscriptions.viewTrashed',
        'subscriptions.activate',
        'subscriptions.cancel',
        'subscription_plans.viewAny',
        'subscription_plans.view',
        'subscription_plans.create',
        'subscription_plans.update',
        'subscription_plans.delete',
        'subscription_plans.restore',
        'subscription_plans.forceDelete',
        'subscription_plans.viewTrashed',
        'subscription_plans.activate',
        'subscription_plans.deactivate',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => self::GUARD]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
