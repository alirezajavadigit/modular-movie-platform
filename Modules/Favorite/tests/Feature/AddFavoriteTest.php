<?php

namespace Modules\Favorite\Tests\Feature;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Modules\Favorite\Models\Favorite;
use Modules\Favorite\Tests\TestCase;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;

class AddFavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_a_favorite(): void
    {
        $user   = User::factory()->create();
        $target = Episode::factory()->create();

        $response = $this->actingAs($user)->postJson(route('api.v1.favorites.store'), [
            'favoriteable_type' => 'episode',
            'favoriteable_id'   => $target->id,
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'user_id', 'favoritable_type', 'favoritable_id', 'created_at']]);

        $this->assertDatabaseHas('favorites', [
            'user_id'           => $user->id,
            'favoriteable_id'   => $target->id,
            'favoriteable_type' => Relation::getMorphAlias(Episode::class),
        ]);
    }

    public function test_response_data_contains_correct_transformer_fields(): void
    {
        $user   = User::factory()->create();
        $target = Article::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.favorites.store'), [
            'favoriteable_type' => 'article',
            'favoriteable_id'   => $target->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.favoritable_id', $target->id)
            ->assertJsonPath('data.favoritable_type', 'article');
    }

    public function test_adding_the_same_favorite_twice_is_idempotent(): void
    {
        $user   = User::factory()->create();
        $target = Movie::factory()->create();

        Favorite::factory()->forUser($user)->forFavoriteable($target, 'movie')->create();

        $this->actingAs($user)->postJson(route('api.v1.favorites.store'), [
            'favoriteable_type' => 'movie',
            'favoriteable_id'   => $target->id,
        ])->assertOk();

        $this->assertSame(1, Favorite::where('user_id', $user->id)->count());
    }

    public function test_unauthenticated_user_cannot_add_a_favorite(): void
    {
        $target = User::factory()->create();

        $this->postJson(route('api.v1.favorites.store'), [
            'favoriteable_type' => 'user',
            'favoriteable_id'   => $target->id,
        ])->assertUnauthorized();
    }

    public function test_store_fails_with_unsupported_model_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.favorites.store'), [
            'favoriteable_type' => 'invalid_model',
            'favoriteable_id'   => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['favoriteable_type']);
    }

    public function test_validation_error_response_has_unified_structure(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.favorites.store'), [
            'favoriteable_type' => 'invalid_model',
            'favoriteable_id'   => 1,
        ])->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_store_fails_when_favoriteable_id_is_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.favorites.store'), [
            'favoriteable_type' => 'user',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['favoriteable_id']);
    }

    public function test_store_fails_when_favoriteable_id_is_not_a_positive_integer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.favorites.store'), [
            'favoriteable_type' => 'user',
            'favoriteable_id'   => 0,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['favoriteable_id']);
    }

    public function test_store_fails_when_favoriteable_type_is_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.favorites.store'), [
            'favoriteable_id' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['favoriteable_type']);
    }

    public function test_each_user_can_independently_favorite_the_same_item(): void
    {
        $userA  = User::factory()->create();
        $userB  = User::factory()->create();
        $target = Episode::factory()->create();

        $this->actingAs($userA)->postJson(route('api.v1.favorites.store'), [
            'favoriteable_type' => 'episode',
            'favoriteable_id'   => $target->id,
        ])->assertCreated();

        $this->actingAs($userB)->postJson(route('api.v1.favorites.store'), [
            'favoriteable_type' => 'episode',
            'favoriteable_id'   => $target->id,
        ])->assertCreated();

        $this->assertSame(2, Favorite::where('favoriteable_id', $target->id)->count());
    }
}
