<?php

namespace Modules\Like\Tests\Feature;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Like\Models\Like;
use Modules\Like\Tests\TestCase;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Modules\Person\Models\Person;

class ToggleLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_adds_like_when_not_yet_liked(): void
    {
        $user   = User::factory()->create();
        $target = Movie::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.likes.toggle'), [
            'likeable_type' => 'movie',
            'likeable_id'   => $target->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.liked', true);

        $this->assertDatabaseHas('likes', [
            'user_id'      => $user->id,
            'likeable_id'  => $target->id,
            'likeable_type' => Relation::getMorphAlias(Movie::class),
        ]);
    }

    public function test_toggle_removes_like_when_already_liked(): void
    {
        $user   = User::factory()->create();
        $target = Episode::factory()->create();

        Like::factory()->forUser($user)->forLikeable($target, 'episode')->create();

        $this->actingAs($user)->postJson(route('api.v1.likes.toggle'), [
            'likeable_type' => 'episode',
            'likeable_id'   => $target->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.liked', false);

        $this->assertDatabaseMissing('likes', [
            'user_id'      => $user->id,
            'likeable_id'  => $target->id,
            'likeable_type' => 'episode',
        ]);
    }

    public function test_toggle_response_includes_updated_count(): void
    {
        $user   = User::factory()->create();
        $userB  = User::factory()->create();
        $target = Person::factory()->create();

        Like::factory()->forUser($userB)->forLikeable($target, 'person')->create();

        $this->actingAs($user)->postJson(route('api.v1.likes.toggle'), [
            'likeable_type' => 'person',
            'likeable_id'   => $target->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('data.count', 2);
    }

    public function test_double_toggle_leaves_no_likes(): void
    {
        $user   = User::factory()->create();
        $target = Movie::factory()->create();

        $payload = [
            'likeable_type' => 'movie',
            'likeable_id'   => $target->id,
        ];

        $this->actingAs($user)->postJson(route('api.v1.likes.toggle'), $payload);
        $this->actingAs($user)->postJson(route('api.v1.likes.toggle'), $payload);

        $this->assertSame(0, Like::where('user_id', $user->id)->count());
    }

    public function test_unauthenticated_user_cannot_toggle_a_like(): void
    {
        $target = Movie::factory()->create();

        $this->postJson(route('api.v1.likes.toggle'), [
            'likeable_type' => 'movie',
            'likeable_id'   => $target->id,
        ])->assertUnauthorized();
    }

    public function test_toggle_fails_with_unsupported_model_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.likes.toggle'), [
            'likeable_type' => 'invalid_type',
            'likeable_id'   => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['likeable_type']);
    }

    public function test_toggle_validation_error_has_unified_structure(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.likes.toggle'), [
            'likeable_type' => 'invalid_type',
        ])->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_toggle_count_reflects_multiple_users(): void
    {
        $users  = User::factory()->count(3)->create();
        $target = Movie::factory()->create();

        foreach ($users as $user) {
            Like::factory()->forUser($user)->forLikeable($target, 'movie')->create();
        }

        $newUser = User::factory()->create();

        $this->actingAs($newUser)->postJson(route('api.v1.likes.toggle'), [
            'likeable_type' => 'movie',
            'likeable_id'   => $target->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('data.count', 4);
    }
}
