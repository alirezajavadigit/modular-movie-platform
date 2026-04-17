<?php

namespace Modules\Category\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Category\Models\Category;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'parent_id'   => null,
            'name'        => ['en' => ucfirst($name)],
            'slug'        => ['en' => Str::slug($name)],
            'description' => ['en' => $this->faker->sentence()],
            'is_active'   => true,
            'order'       => $this->faker->numberBetween(0, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['is_active' => false]);
    }

    public function childOf(Category $parent): static
    {
        return $this->state(fn() => ['parent_id' => $parent->id]);
    }
}
