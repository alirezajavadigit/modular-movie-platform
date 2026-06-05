<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Models\Permission;

class UserPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'users.viewAny',
            'users.view',
            'users.viewOwn',
            'users.create',
            'users.update',
            'users.updateOwn',
            'users.delete',
            'users.restore',
            'users.forceDelete',
            'users.viewTrashed',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'api');
        }
    }
}
