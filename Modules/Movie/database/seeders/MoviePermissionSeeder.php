<?php

namespace Modules\Movie\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Models\Permission;

class MoviePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'movies.viewAny',
            'movies.view',
            'movies.create',
            'movies.update',
            'movies.delete',
            'movies.restore',
            'episodes.viewAny',
            'episodes.view',
            'episodes.create',
            'episodes.update',
            'episodes.delete',
            'episodes.restore',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'api');
        }
    }
}
