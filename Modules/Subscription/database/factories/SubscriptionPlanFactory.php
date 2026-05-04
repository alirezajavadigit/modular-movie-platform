<?php

namespace Modules\Subscription\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Subscription\Enums\SubscriptionPlanStatus;
use Modules\Subscription\Models\SubscriptionPlan;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'name'          => $this->faker->words(3, true),
            'description'   => $this->faker->paragraph(),
            'price'         => $this->faker->randomFloat(2, 5, 500),
            'duration_days' => $this->faker->randomElement([30, 90, 180, 365]),
            'status'        => SubscriptionPlanStatus::ACTIVE->value,
        ];
    }

    public function active(): static
    {
        return $this->state(fn() => [
            'status' => SubscriptionPlanStatus::ACTIVE->value,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn() => [
            'status' => SubscriptionPlanStatus::INACTIVE->value,
        ]);
    }
}
