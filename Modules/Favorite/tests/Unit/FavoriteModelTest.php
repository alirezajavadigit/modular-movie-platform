<?php

namespace Modules\Favorite\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Modules\Favorite\Models\Favorite;
use Modules\Favorite\Tests\TestCase;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Modules\Person\Models\Person;

class FavoriteModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_relationship_returns_belongs_to(): void
    {
        $this->assertInstanceOf(BelongsTo::class, (new Favorite())->user());
    }

    public function test_favoriteable_relationship_returns_morph_to(): void
    {
        $this->assertInstanceOf(MorphTo::class, (new Favorite())->favoriteable());
    }

    public function test_fillable_fields_are_correct(): void
    {
        $this->assertSame(
            ['user_id', 'favoriteable_id', 'favoriteable_type'],
            (new Favorite())->getFillable(),
        );
    }

    public function test_for_user_scope_filters_by_user_id(): void
    {
        $userA  = User::factory()->create();
        $userB  = User::factory()->create();
        $target = Article::factory()->create();

        Favorite::factory()->forUser($userA)->forFavoriteable($target, "article")->create();
        Favorite::factory()->forUser($userB)->forFavoriteable($target, "article")->create();

        $results = Favorite::query()->forUser($userA->id)->get();

        $this->assertCount(1, $results);
        $this->assertSame($userA->id, $results->first()->user_id);
    }

    public function test_for_favoriteable_scope_filters_by_type_and_id(): void
    {
        $user    = User::factory()->create();
        $targetA = Movie::factory()->create();
        $targetB = Article::factory()->create();

        Favorite::factory()->forUser($user)->forFavoriteable($targetA, 'movie')->create();
        Favorite::factory()->forUser($user)->forFavoriteable($targetB, 'article')->create();

        $results = Favorite::query()->forFavoriteable('movie', $targetA->id)->get();

        $this->assertCount(1, $results);
        $this->assertSame($targetA->id, $results->first()->favoriteable_id);
    }

    public function test_favorite_belongs_to_a_user(): void
    {
        $user     = User::factory()->create();
        $target   = Person::factory()->create();
        $favorite = Favorite::factory()->forUser($user)->forFavoriteable($target, 'person')->create();

        $this->assertSame($user->id, $favorite->fresh()->user->id);
    }

    public function test_favorite_resolves_morph_to_correct_model(): void
    {
        $user     = User::factory()->create();
        $target   = Episode::factory()->create();

        $favorite = Favorite::factory()->forUser($user)->forFavoriteable($target, 'episode')->create();

        $this->assertInstanceOf(Episode::class, $favorite->fresh()->favoriteable);
        $this->assertSame($target->id, $favorite->fresh()->favoriteable->id);
    }

    public function test_deleting_a_user_cascades_to_their_favorites(): void
    {
        $user   = User::factory()->create();
        $target = Article::factory()->create();

        Favorite::factory()->forUser($user)->forFavoriteable($target, 'article')->create();

        $user->forceDelete();

        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id]);
    }
}
