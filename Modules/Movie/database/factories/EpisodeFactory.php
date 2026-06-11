<?php

namespace Modules\Movie\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;

class EpisodeFactory extends Factory
{
    protected $model = Episode::class;

    public function definition(): array
    {
        return [
            'movie_id'       => Movie::factory()->serial(),
            'season_number'  => $this->faker->numberBetween(1, 10),
            'episode_number' => $this->faker->numberBetween(1, 24),
            'title'          => ['en' => $this->faker->sentence(3)],
            'description'    => ['en' => $this->faker->paragraph()],
            'poster'         => $this->faker->imageUrl(),
            'trailer_url'    => $this->faker->url(),
            'download_links' => [$this->faker->url()],
        ];
    }
}
