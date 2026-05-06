<?php

namespace Modules\Like\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Modules\Like\Models\Like;
use Modules\Like\Tests\TestCase;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Modules\Person\Models\Person;

class ListLikesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_their_likes(): void
    {
        $user    = User::factory()->create();
        $targets = Person::factory()->count(3)->create();

        foreach ($targets as $target) {
            Like::factory()->forUser($user)->forLikeable($target, 'person')->create();
        }

        $this->actingAs($user)
            ->getJson(route('api.v1.likes.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'user_id', 'likeable_type', 'likeable_id', 'created_at']]]);
    }

    public function test_user_only_sees_their_own_likes(): void
    {
        $userA  = User::factory()->create();
        $userB  = User::factory()->create();
        $target = Movie::factory()->create();

        Like::factory()->forUser($userA)->forLikeable($target, 'movie')->create();
        Like::factory()->forUser($userB)->forLikeable($target, 'movie')->create();

        $this->actingAs($userA)
            ->getJson(route('api.v1.likes.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_unauthenticated_user_cannot_list_likes(): void
    {
        $this->getJson(route('api.v1.likes.index'))
            ->assertUnauthorized();
    }

    public function test_likes_are_paginated(): void
    {
        $user    = User::factory()->create();
        $targets = Episode::factory()->count(20)->create();

        foreach ($targets as $target) {
            Like::factory()->forUser($user)->forLikeable($target, 'episode')->create();
        }

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.likes.index') . '?per_page=5')
            ->assertOk();

        $this->assertSame(5, count($response->json('data')));
        $this->assertSame(20, $response->json('meta.total'));
    }

    public function test_per_page_is_capped_at_100(): void
    {
        $user = User::factory()->create();

        Article::factory()->count(5)->create()->each(
            fn($t) => Like::factory()->forUser($user)->forLikeable($t, 'article')->create(),
        );

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.likes.index') . '?per_page=999')
            ->assertOk();

        $this->assertLessThanOrEqual(100, count($response->json('data')));
    }

    public function test_user_with_no_likes_gets_empty_paginated_result(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.likes.index'))
            ->assertOk();

        $this->assertSame(0, $response->json('meta.total'));
        $this->assertEmpty($response->json('data'));
    }

    public function test_list_items_belong_to_the_authenticated_user(): void
    {
        $user   = User::factory()->create();
        $target = Movie::factory()->create();

        Like::factory()->forUser($user)->forLikeable($target, 'movie')->create();

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.likes.index'))
            ->assertOk();

        $this->assertSame($user->id, $response->json('data.0.user_id'));
    }
}
