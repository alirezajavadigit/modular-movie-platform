<?php

namespace Modules\Subscription\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Subscription;
use Modules\Subscription\Models\SubscriptionPlan;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'plan_id'    => SubscriptionPlan::factory(),
            'payment_id' => null,
            'starts_at'  => null,
            'ends_at'    => null,
            'status'     => SubscriptionStatus::PENDING->value,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn() => [
            'status'    => SubscriptionStatus::PENDING->value,
            'starts_at' => null,
            'ends_at'   => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn() => [
            'status'    => SubscriptionStatus::ACTIVE->value,
            'starts_at' => now(),
            'ends_at'   => now()->addDays(30),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'status'    => SubscriptionStatus::EXPIRED->value,
            'starts_at' => now()->subDays(60),
            'ends_at'   => now()->subDays(30),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn() => [
            'status' => SubscriptionStatus::CANCELED->value,
        ]);
    }
}
