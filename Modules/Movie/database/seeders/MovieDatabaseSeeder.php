<?php

namespace Modules\Movie\Database\Seeders;

use Illuminate\Database\Seeder;

class MovieDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MoviePermissionSeeder::class,
        ]);
    }
}
