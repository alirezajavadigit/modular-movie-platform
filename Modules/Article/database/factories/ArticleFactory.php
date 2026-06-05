<?php

namespace Modules\Article\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);

        return [
            'user_id'        => User::factory(),
            'title'          => ['en' => $title],
            'slug'           => ['en' => Str::slug($title) . '-' . $this->faker->unique()->numberBetween(1, 999999)],
            'summary'        => ['en' => $this->faker->sentence(10)],
            'body'           => ['en' => $this->faker->paragraphs(3, true)],
            'status'         => 'draft',
            'read_time'      => $this->faker->numberBetween(1, 30),
            'is_featured'    => false,
            'allow_comments' => true,
            'published_at'   => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn() => [
            'status'       => 'published',
            'published_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn() => [
            'status'       => 'draft',
            'published_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn() => ['status' => 'archived']);
    }

    public function featured(): static
    {
        return $this->state(fn() => ['is_featured' => true]);
    }
}
