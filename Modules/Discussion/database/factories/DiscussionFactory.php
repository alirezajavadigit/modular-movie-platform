<?php

namespace Modules\Discussion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Discussion\Enums\DiscussionStatus;
use Modules\Discussion\Models\Discussion;

class DiscussionFactory extends Factory
{
    protected $model = Discussion::class;

    public function definition(): array
    {
        return [
            'user_id'             => User::factory(),
            'parent_id'           => null,
            'discussionable_id'   => 1,
            'discussionable_type' => 'Modules\\Movie\\Models\\Movie',
            'body'                => $this->faker->paragraph(),
            'status'              => DiscussionStatus::PENDING,
            'ip_address'          => $this->faker->ipv4(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DiscussionStatus::PENDING,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DiscussionStatus::APPROVED,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DiscussionStatus::REJECTED,
        ]);
    }

    public function reply(?Discussion $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->id ?? Discussion::factory(),
        ]);
    }
}
