<?php

namespace Modules\Favorite\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Modules\Favorite\Models\Favorite;
use Modules\Favorite\Tests\TestCase;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Modules\Person\Models\Person;

class ListFavoritesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_their_favorites(): void
    {
        $user    = User::factory()->create();
        $targets = Person::factory()->count(3)->create();

        foreach ($targets as $target) {
            Favorite::factory()->forUser($user)->forFavoriteable($target, 'person')->create();
        }

        $this->actingAs($user)
            ->getJson(route('api.v1.favorites.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'user_id', 'favoritable_type', 'favoritable_id', 'created_at']]]);
    }

    public function test_user_only_sees_their_own_favorites(): void
    {
        $userA  = User::factory()->create();
        $userB  = User::factory()->create();
        $target = Movie::factory()->create();

        Favorite::factory()->forUser($userA)->forFavoriteable($target, 'movie')->create();
        Favorite::factory()->forUser($userB)->forFavoriteable($target, 'movie')->create();

        $this->actingAs($userA)
            ->getJson(route('api.v1.favorites.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_unauthenticated_user_cannot_list_favorites(): void
    {
        $this->getJson(route('api.v1.favorites.index'))
            ->assertUnauthorized();
    }

    public function test_favorites_are_paginated(): void
    {
        $user    = User::factory()->create();
        $targets = Episode::factory()->count(20)->create();

        foreach ($targets as $target) {
            Favorite::factory()->forUser($user)->forFavoriteable($target, 'episode')->create();
        }

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.favorites.index') . '?per_page=5')
            ->assertOk();

        $this->assertSame(5, count($response->json('data')));
        $this->assertSame(20, $response->json('meta.total'));
    }

    public function test_per_page_is_capped_at_100(): void
    {
        $user = User::factory()->create();

        Article::factory()->count(5)->create()->each(
            fn($t) => Favorite::factory()->forUser($user)->forFavoriteable($t, 'article')->create(),
        );

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.favorites.index') . '?per_page=999')
            ->assertOk();

        $this->assertLessThanOrEqual(100, count($response->json('data')));
    }

    public function test_user_with_no_favorites_gets_empty_paginated_result(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.favorites.index'))
            ->assertOk();

        $this->assertSame(0, $response->json('meta.total'));
        $this->assertEmpty($response->json('data'));
    }

    public function test_list_items_belong_to_the_authenticated_user(): void
    {
        $user   = User::factory()->create();
        $target = Movie::factory()->create();

        Favorite::factory()->forUser($user)->forFavoriteable($target, 'movie')->create();

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.favorites.index'))
            ->assertOk();

        $this->assertSame($user->id, $response->json('data.0.user_id'));
    }
}
