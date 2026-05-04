<?php

namespace Modules\Favorite\Tests\Feature;

use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Favorite\Models\Favorite;
use Modules\Favorite\Tests\TestCase;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Modules\Person\Models\Person;

class ToggleFavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_adds_favorite_when_not_yet_favorited(): void
    {
        $user   = User::factory()->create();
        $target = Movie::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.favorites.toggle'), [
            'favoriteable_type' => 'movie',
            'favoriteable_id'   => $target->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.favorited', true);

        $this->assertDatabaseHas('favorites', [
            'user_id'           => $user->id,
            'favoriteable_id'   => $target->id,
            'favoriteable_type' => "movie",
        ]);
    }

    public function test_toggle_removes_favorite_when_already_favorited(): void
    {
        $user   = User::factory()->create();
        $target = Episode::factory()->create();

        Favorite::factory()->forUser($user)->forFavoriteable($target, 'episode')->create();

        $this->actingAs($user)->postJson(route('api.v1.favorites.toggle'), [
            'favoriteable_type' => 'episode',
            'favoriteable_id'   => $target->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.favorited', false);

        $this->assertDatabaseMissing('favorites', [
            'user_id'           => $user->id,
            'favoriteable_id'   => $target->id,
            'favoriteable_type' => 'episode',
        ]);
    }

    public function test_toggle_response_includes_updated_count(): void
    {
        $user   = User::factory()->create();
        $userB  = User::factory()->create();
        $target = Person::factory()->create();

        Favorite::factory()->forUser($userB)->forFavoriteable($target, 'person')->create();

        $this->actingAs($user)->postJson(route('api.v1.favorites.toggle'), [
            'favoriteable_type' => 'person',
            'favoriteable_id'   => $target->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.favorited', true)
            ->assertJsonPath('data.count', 2);
    }

    public function test_double_toggle_leaves_no_favorites(): void
    {
        $user   = User::factory()->create();
        $target = User::factory()->create();

        $payload = [
            'favoriteable_type' => 'user',
            'favoriteable_id'   => $target->id,
        ];

        $this->actingAs($user)->postJson(route('api.v1.favorites.toggle'), $payload);
        $this->actingAs($user)->postJson(route('api.v1.favorites.toggle'), $payload);

        $this->assertSame(0, Favorite::where('user_id', $user->id)->count());
    }

    public function test_unauthenticated_user_cannot_toggle_a_favorite(): void
    {
        $target = User::factory()->create();

        $this->postJson(route('api.v1.favorites.toggle'), [
            'favoriteable_type' => 'user',
            'favoriteable_id'   => $target->id,
        ])->assertUnauthorized();
    }

    public function test_toggle_fails_with_unsupported_model_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.favorites.toggle'), [
            'favoriteable_type' => 'invalid_type',
            'favoriteable_id'   => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['favoriteable_type']);
    }

    public function test_toggle_validation_error_has_unified_structure(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.favorites.toggle'), [
            'favoriteable_type' => 'invalid_type',
        ])->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors']);
    }
}
