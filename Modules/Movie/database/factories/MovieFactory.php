<?php

namespace Modules\Movie\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Movie\Enums\BadgeType;
use Modules\Movie\Enums\MovieType;
use Modules\Movie\Models\Movie;

class MovieFactory extends Factory
{
    protected $model = Movie::class;

    public function definition(): array
    {
        return [
            'title'          => $this->faker->sentence(3),
            'description'    => $this->faker->paragraph(),
            'poster'         => $this->faker->imageUrl(),
            'trailer_url'    => $this->faker->url(),
            'download_links' => [$this->faker->url(), $this->faker->url()],
            'release_year'   => $this->faker->numberBetween(1980, 2026),
            'country'        => $this->faker->country(),
            'language'       => $this->faker->languageCode(),
            'imdb_score'     => $this->faker->randomFloat(1, 1, 10),
            'badge'          => $this->faker->randomElement(BadgeType::cases()),
            'type'           => $this->faker->randomElement(MovieType::cases()),
        ];
    }

    public function movie(): static
    {
        return $this->state(['type' => MovieType::Movie]);
    }

    public function serial(): static
    {
        return $this->state(['type' => MovieType::Serial]);
    }

    public function dubbed(): static
    {
        return $this->state(['badge' => BadgeType::Dubbed]);
    }

    public function subtitled(): static
    {
        return $this->state(['badge' => BadgeType::Subtitled]);
    }

    public function animation(): static
    {
        return $this->state(['badge' => BadgeType::Animation]);
    }
}
