<?php

namespace Modules\Person\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Person\Enums\CreditRole;
use Modules\Person\Models\Credit;
use Modules\Person\Models\Person;

class CreditFactory extends Factory
{
    protected $model = Credit::class;

    public function definition(): array
    {
        return [
            'person_id'       => Person::factory(),
            'creditable_id'   => 1,
            'creditable_type' => 'movie',
            'role'            => $this->faker->randomElement(CreditRole::values()),
            'character_name'  => null,
            'credited_as'     => null,
            'department'      => null,
            'order'           => $this->faker->numberBetween(0, 100),
        ];
    }

    public function asActor(?string $character = null): static
    {
        return $this->state(fn() => [
            'role'           => CreditRole::ACTOR->value,
            'character_name' => $character ?? $this->faker->name(),
        ]);
    }

    public function asDirector(): static
    {
        return $this->state(fn() => [
            'role'       => CreditRole::DIRECTOR->value,
            'department' => 'Directing',
        ]);
    }

    public function forCreditable(string $morphAlias, int $id): static
    {
        return $this->state(fn() => [
            'creditable_type' => $morphAlias,
            'creditable_id'   => $id,
        ]);
    }
}
