<?php

namespace Modules\Like\Tests\Feature;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Modules\Like\Models\Like;
use Modules\Like\Tests\TestCase;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;

class AddLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_a_like(): void
    {
        $user   = User::factory()->create();
        $target = Episode::factory()->create();

        $response = $this->actingAs($user)->postJson(route('api.v1.likes.store'), [
            'likeable_type' => 'episode',
            'likeable_id'   => $target->id,
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'user_id', 'likeable_type', 'likeable_id', 'created_at']]);

        $this->assertDatabaseHas('likes', [
            'user_id'      => $user->id,
            'likeable_id'  => $target->id,
            'likeable_type' => Relation::getMorphAlias(Episode::class),
        ]);
    }

    public function test_response_data_contains_correct_transformer_fields(): void
    {
        $user   = User::factory()->create();
        $target = Article::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.likes.store'), [
            'likeable_type' => 'article',
            'likeable_id'   => $target->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.likeable_id', $target->id)
            ->assertJsonPath('data.likeable_type', 'article');
    }

    public function test_adding_the_same_like_twice_is_idempotent(): void
    {
        $user   = User::factory()->create();
        $target = Movie::factory()->create();

        Like::factory()->forUser($user)->forLikeable($target, 'movie')->create();

        $this->actingAs($user)->postJson(route('api.v1.likes.store'), [
            'likeable_type' => 'movie',
            'likeable_id'   => $target->id,
        ])->assertOk();

        $this->assertSame(1, Like::where('user_id', $user->id)->count());
    }

    public function test_unauthenticated_user_cannot_add_a_like(): void
    {
        $target = Movie::factory()->create();

        $this->postJson(route('api.v1.likes.store'), [
            'likeable_type' => 'movie',
            'likeable_id'   => $target->id,
        ])->assertUnauthorized();
    }

    public function test_store_fails_with_unsupported_model_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.likes.store'), [
            'likeable_type' => 'invalid_model',
            'likeable_id'   => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['likeable_type']);
    }

    public function test_validation_error_response_has_unified_structure(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.likes.store'), [
            'likeable_type' => 'invalid_model',
            'likeable_id'   => 1,
        ])->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_store_fails_when_likeable_id_is_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.likes.store'), [
            'likeable_type' => 'movie',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['likeable_id']);
    }

    public function test_store_fails_when_likeable_id_is_not_a_positive_integer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.likes.store'), [
            'likeable_type' => 'movie',
            'likeable_id'   => 0,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['likeable_id']);
    }

    public function test_store_fails_when_likeable_type_is_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.likes.store'), [
            'likeable_id' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['likeable_type']);
    }

    public function test_each_user_can_independently_like_the_same_item(): void
    {
        $userA  = User::factory()->create();
        $userB  = User::factory()->create();
        $target = Episode::factory()->create();

        $this->actingAs($userA)->postJson(route('api.v1.likes.store'), [
            'likeable_type' => 'episode',
            'likeable_id'   => $target->id,
        ])->assertCreated();

        $this->actingAs($userB)->postJson(route('api.v1.likes.store'), [
            'likeable_type' => 'episode',
            'likeable_id'   => $target->id,
        ])->assertCreated();

        $this->assertSame(2, Like::where('likeable_id', $target->id)->count());
    }
}
