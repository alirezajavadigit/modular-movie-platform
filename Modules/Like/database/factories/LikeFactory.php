<?php

namespace Modules\Like\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Like\Models\Like;
use Modules\Movie\Models\Movie;

class LikeFactory extends Factory
{
    protected $model = Like::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'likeable_id'  => 1,
            'likeable_type' => Movie::class,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }

    public function forLikeable(Model $model, string $morphAlias): static
    {
        return $this->state([
            'likeable_id'  => $model->getKey(),
            'likeable_type' => $morphAlias,
        ]);
    }
}
