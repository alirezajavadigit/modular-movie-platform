<?php

namespace Modules\Payment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payable_type'   => User::class,
            'payable_id'     => User::factory(),
            'user_id'        => User::factory(),
            'amount'         => $this->faker->randomFloat(2, 10, 10000),
            'driver'         => $this->faker->randomElement(['zarinpal', 'mellat', 'saman']),
            'transaction_id' => null,
            'status'         => PaymentStatus::PENDING->value,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn() => [
            'status'         => PaymentStatus::SUCCESS->value,
            'transaction_id' => $this->faker->uuid(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn() => [
            'status' => PaymentStatus::FAILED->value,
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn() => [
            'status' => PaymentStatus::CANCELED->value,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn() => [
            'status'         => PaymentStatus::REFUNDED->value,
            'transaction_id' => $this->faker->uuid(),
        ]);
    }
}
