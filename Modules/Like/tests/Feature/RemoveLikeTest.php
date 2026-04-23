<?php

namespace Modules\Like\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Modules\Like\Models\Like;
use Modules\Like\Tests\TestCase;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;

class RemoveLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_remove_their_own_like(): void
    {
        $user   = User::factory()->create();
        $target = Article::factory()->create();
        $like   = Like::factory()->forUser($user)->forLikeable($target, 'article')->create();

        $this->actingAs($user)
            ->deleteJson(route('api.v1.likes.destroy', $like))
            ->assertNoContent();

        $this->assertDatabaseMissing('likes', ['id' => $like->id]);
    }

    public function test_unauthenticated_user_cannot_remove_a_like(): void
    {
        $user   = User::factory()->create();
        $target = Article::factory()->create();
        $like   = Like::factory()->forUser($user)->forLikeable($target, 'article')->create();

        $this->deleteJson(route('api.v1.likes.destroy', $like))
            ->assertUnauthorized();
    }

    public function test_removing_a_non_existent_like_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('api.v1.likes.destroy', 99999))
            ->assertNotFound();
    }

    public function test_remove_deletes_exactly_one_record_and_leaves_others_intact(): void
    {
        $user    = User::factory()->create();
        $targetA = Movie::factory()->create();
        $targetB = Episode::factory()->create();

        $likeA = Like::factory()->forUser($user)->forLikeable($targetA, 'movie')->create();
        $likeB = Like::factory()->forUser($user)->forLikeable($targetB, 'episode')->create();

        $this->actingAs($user)
            ->deleteJson(route('api.v1.likes.destroy', $likeA))
            ->assertNoContent();

        $this->assertDatabaseMissing('likes', ['id' => $likeA->id]);
        $this->assertDatabaseHas('likes', ['id' => $likeB->id]);
    }
}
