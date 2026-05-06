<?php

namespace Modules\Like\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Modules\Like\Models\Like;
use Modules\Like\Tests\TestCase;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Modules\Person\Models\Person;

class LikeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_relationship_returns_belongs_to(): void
    {
        $this->assertInstanceOf(BelongsTo::class, (new Like())->user());
    }

    public function test_likeable_relationship_returns_morph_to(): void
    {
        $this->assertInstanceOf(MorphTo::class, (new Like())->likeable());
    }

    public function test_fillable_fields_are_correct(): void
    {
        $this->assertSame(
            ['user_id', 'likeable_id', 'likeable_type'],
            (new Like())->getFillable(),
        );
    }

    public function test_for_user_scope_filters_by_user_id(): void
    {
        $userA  = User::factory()->create();
        $userB  = User::factory()->create();
        $target = Article::factory()->create();

        Like::factory()->forUser($userA)->forLikeable($target, 'article')->create();
        Like::factory()->forUser($userB)->forLikeable($target, 'article')->create();

        $results = Like::query()->forUser($userA->id)->get();

        $this->assertCount(1, $results);
        $this->assertSame($userA->id, $results->first()->user_id);
    }

    public function test_for_likeable_scope_filters_by_type_and_id(): void
    {
        $user    = User::factory()->create();
        $targetA = Movie::factory()->create();
        $targetB = Article::factory()->create();

        Like::factory()->forUser($user)->forLikeable($targetA, 'movie')->create();
        Like::factory()->forUser($user)->forLikeable($targetB, 'article')->create();

        $results = Like::query()->forLikeable('movie', $targetA->id)->get();

        $this->assertCount(1, $results);
        $this->assertSame($targetA->id, $results->first()->likeable_id);
    }

    public function test_like_belongs_to_a_user(): void
    {
        $user   = User::factory()->create();
        $target = Person::factory()->create();
        $like   = Like::factory()->forUser($user)->forLikeable($target, 'person')->create();

        $this->assertSame($user->id, $like->fresh()->user->id);
    }

    public function test_like_resolves_morph_to_correct_model(): void
    {
        $user   = User::factory()->create();
        $target = Episode::factory()->create();
        $like   = Like::factory()->forUser($user)->forLikeable($target, 'episode')->create();

        $this->assertInstanceOf(Episode::class, $like->fresh()->likeable);
        $this->assertSame($target->id, $like->fresh()->likeable->id);
    }

    public function test_deleting_a_user_cascades_to_their_likes(): void
    {
        $user   = User::factory()->create();
        $target = Article::factory()->create();

        Like::factory()->forUser($user)->forLikeable($target, 'article')->create();

        $user->forceDelete();

        $this->assertDatabaseMissing('likes', ['user_id' => $user->id]);
    }

    public function test_unique_constraint_prevents_duplicate_likes(): void
    {
        $user   = User::factory()->create();
        $target = Movie::factory()->create();

        Like::factory()->forUser($user)->forLikeable($target, 'movie')->create();

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Like::factory()->forUser($user)->forLikeable($target, 'movie')->create();
    }
}
