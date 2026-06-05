<?php

namespace Modules\Favorite\Tests\Feature;

use Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Favorite\Models\Favorite;
use Modules\Favorite\Tests\TestCase;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;

class RemoveFavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_remove_their_own_favorite(): void
    {
        $user     = User::factory()->create();
        $target   = Article::factory()->create();
        $favorite = Favorite::factory()->forUser($user)->forFavoriteable($target, 'article')->create();

        $this->actingAs($user)
            ->deleteJson(route('api.v1.favorites.destroy', $favorite))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', ['id' => $favorite->id]);
    }


    public function test_unauthenticated_user_cannot_remove_a_favorite(): void
    {
        $user     = User::factory()->create();
        $target   = Article::factory()->create();
        $favorite = Favorite::factory()->forUser($user)->forFavoriteable($target, 'article')->create();

        $this->deleteJson(route('api.v1.favorites.destroy', $favorite))
            ->assertUnauthorized();
    }

    public function test_removing_a_non_existent_favorite_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('api.v1.favorites.destroy', 99999))
            ->assertNotFound();
    }

    public function test_remove_deletes_exactly_one_record_and_leaves_others_intact(): void
    {
        $user    = User::factory()->create();
        $targetA = Movie::factory()->create();
        $targetB = Episode::factory()->create();

        $favA = Favorite::factory()->forUser($user)->forFavoriteable($targetA, 'movie')->create();
        $favB = Favorite::factory()->forUser($user)->forFavoriteable($targetB, 'episode')->create();

        $this->actingAs($user)
            ->deleteJson(route('api.v1.favorites.destroy', $favA))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', ['id' => $favA->id]);
        $this->assertDatabaseHas('favorites', ['id' => $favB->id]);
    }
}
