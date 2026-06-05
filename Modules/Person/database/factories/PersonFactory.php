<?php

namespace Modules\Person\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Person\Enums\Gender;
use Modules\Person\Models\Person;

class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        $first = $this->faker->firstName();
        $last  = $this->faker->lastName();

        return [
            'first_name'           => ['en' => $first],
            'last_name'            => ['en' => $last],
            'slug'                 => Str::slug($first . '-' . $last) . '-' . $this->faker->unique()->numberBetween(1, 999999),
            'biography'            => ['en' => $this->faker->paragraph(4)],
            'date_of_birth'        => $this->faker->dateTimeBetween('-80 years', '-20 years')->format('Y-m-d'),
            'date_of_death'        => null,
            'place_of_birth'       => ['en' => $this->faker->city() . ', ' . $this->faker->country()],
            'gender'               => $this->faker->randomElement(Gender::values()),
            'known_for_department' => $this->faker->randomElement(['Acting', 'Directing', 'Writing', 'Production', 'Sound', 'Camera']),
            'popularity'           => $this->faker->randomFloat(3, 0, 100),
            'is_active'            => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['is_active' => false]);
    }

    public function deceased(): static
    {
        return $this->state(fn() => [
            'date_of_death' => $this->faker->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
        ]);
    }

    public function actor(): static
    {
        return $this->state(fn() => ['known_for_department' => 'Acting']);
    }

    public function director(): static
    {
        return $this->state(fn() => ['known_for_department' => 'Directing']);
    }
}
