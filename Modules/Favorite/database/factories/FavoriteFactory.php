<?php

namespace Modules\Favorite\Database\Factories;

use Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Modules\Favorite\Models\Favorite;
use Modules\Movie\Models\Movie;

class FavoriteFactory extends Factory
{
    protected $model = Favorite::class;

    public function definition(): array
    {
        return [
            'user_id'           => User::factory(),
            'favoriteable_id'   => 1,
            'favoriteable_type' => Movie::class,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }

    public function forFavoriteable(Model $model, string $name): static
    {
        return $this->state([
            'favoriteable_id'   => $model->getKey(),
            'favoriteable_type' => $name,
        ]);
    }
}
