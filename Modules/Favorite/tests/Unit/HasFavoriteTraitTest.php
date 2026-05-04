<?php

namespace Modules\Favorite\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Modules\Favorite\Models\Favorite;
use Modules\Favorite\Tests\TestCase;
use Modules\Favorite\Traits\HasFavorite;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Modules\Person\Models\Person;

class HasFavoriteTraitTest extends TestCase
{
    use RefreshDatabase;

    public function test_favorites_returns_morph_many_of_the_model(): void
    {
        $owner  = User::factory()->create();
        $target = Article::factory()->create();

        Favorite::factory()->forUser($owner)->forFavoriteable($target, 'article')->create();

        $this->assertCount(1, $target->favorites);
        $this->assertSame($owner->id, $target->favorites->first()->user_id);
    }

    public function test_is_favorited_by_returns_true_when_user_has_favorited(): void
    {
        $user   = User::factory()->create();
        $target = Movie::factory()->create();

        Favorite::factory()->forUser($user)->forFavoriteable($target, 'movie')->create();

        $this->assertTrue($target->isFavoritedBy($user->id));
    }

    public function test_is_favorited_by_returns_false_when_user_has_not_favorited(): void
    {
        $user   = User::factory()->create();
        $target = Episode::factory()->create();

        $this->assertFalse($target->isFavoritedBy($user->id));
    }

    public function test_is_favorited_returns_false_when_unauthenticated(): void
    {
        $target = Movie::factory()->create();

        $this->assertFalse($target->isFavorited());
    }

    public function test_is_favorited_returns_true_for_authenticated_user_who_favorited(): void
    {
        $user   = User::factory()->create();
        $target = Article::factory()->create();

        Favorite::factory()->forUser($user)->forFavoriteable($target, 'article')->create();

        Auth::login($user);

        $this->assertTrue($target->isFavorited());

        Auth::logout();
    }

    public function test_is_favorited_returns_false_for_authenticated_user_who_has_not_favorited(): void
    {
        $user   = User::factory()->create();
        $target = Person::factory()->create();

        Auth::login($user);

        $this->assertFalse($target->isFavorited());

        Auth::logout();
    }

    public function test_favorites_count_returns_correct_total(): void
    {
        $target = Movie::factory()->create();
        $users  = User::factory()->count(4)->create();

        foreach ($users as $user) {
            Favorite::factory()->forUser($user)->forFavoriteable($target, 'movie')->create();
        }

        $this->assertSame(4, $target->favoritesCount());
    }

    public function test_favorites_count_returns_zero_when_no_favorites(): void
    {
        $target = Article::factory()->create();

        $this->assertSame(0, $target->favoritesCount());
    }

    public function test_toggle_favorite_by_adds_when_not_yet_favorited(): void
    {
        $user   = User::factory()->create();
        $target = Movie::factory()->create();

        $result = $target->toggleFavoriteBy($user->id);

        $this->assertTrue($result['favorited']);
        $this->assertSame(1, $result['count']);
        $this->assertDatabaseHas('favorites', [
            'user_id'           => $user->id,
            'favoriteable_id'   => $target->id,
            'favoriteable_type' => 'movie',
        ]);
    }

    public function test_toggle_favorite_by_removes_when_already_favorited(): void
    {
        $user   = User::factory()->create();
        $target = Article::factory()->create();

        Favorite::factory()->forUser($user)->forFavoriteable($target, 'article')->create();

        $result = $target->toggleFavoriteBy($user->id);

        $this->assertFalse($result['favorited']);
        $this->assertSame(0, $result['count']);
        $this->assertDatabaseMissing('favorites', [
            'user_id'           => $user->id,
            'favoriteable_id'   => $target->id,
            'favoriteable_type' => Article::class,
        ]);
    }

    public function test_trait_is_used_by_model_with_has_favorite(): void
    {
        $this->assertContains(HasFavorite::class, class_uses_recursive(Movie::class));
    }
}
