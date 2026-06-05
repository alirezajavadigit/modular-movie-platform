<?php

namespace Modules\Authorization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Authorization\Models\Role;

class RoleFactory extends Factory
{

    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name'       => $this->faker->unique()->word(),
            'guard_name' => 'api',
        ];
    }
}
