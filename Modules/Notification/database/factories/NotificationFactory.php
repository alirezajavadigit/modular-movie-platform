<?php

namespace Modules\Notification\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Models\Notification;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $types = array_keys(config('notification-module.notification_types', [
            'user.welcome' => [],
            'system.announcement' => [],
        ]));

        return [
            'notifiable_type' => User::class,
            'notifiable_id'   => User::factory(),
            'type'            => $this->faker->randomElement($types),
            'channel'         => NotificationChannel::DATABASE,
            'data'            => ['message' => $this->faker->sentence()],
            'read_at'         => null,
            'sent_at'         => now(),
        ];
    }

    public function read(): static
    {
        return $this->state(fn() => [
            'read_at' => now(),
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn() => [
            'read_at' => null,
        ]);
    }

    public function viaEmail(): static
    {
        return $this->state(fn() => [
            'channel' => NotificationChannel::EMAIL,
        ]);
    }

    public function viaSms(): static
    {
        return $this->state(fn() => [
            'channel' => NotificationChannel::SMS,
        ]);
    }

    public function viaPush(): static
    {
        return $this->state(fn() => [
            'channel' => NotificationChannel::PUSH,
        ]);
    }

    public function ofType(string $type): static
    {
        return $this->state(fn() => [
            'type' => $type,
        ]);
    }
}
