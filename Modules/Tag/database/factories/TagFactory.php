<?php

namespace Modules\Tag\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Tag\Models\Tag;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'name'        => ['en' => ucfirst($name)],
            'slug'        => ['en' => Str::slug($name)],
            'description' => ['en' => $this->faker->sentence()],
            'color'       => $this->faker->hexColor(),
            'is_active'   => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['is_active' => false]);
    }
}
