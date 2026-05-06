<?php

namespace Modules\Like\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Models\User;
use Modules\Like\Models\Like;
use Modules\Like\Tests\TestCase;
use Modules\Like\Traits\HasLike;
use Modules\Movie\Models\Movie;

/**
 * A self-contained Eloquent model used exclusively to test the HasLike trait.
 * It maps to the `movies` table so we can resolve real persisted rows.
 * Rows are always created via Movie::factory() (which satisfies all NOT NULL
 * constraints), then re-fetched as this model to gain access to HasLike methods.
 */
class LikeableTestModel extends Model
{
    use HasLike;

    protected $table = 'movies';

    protected $guarded = [];
}

class HasLikeTraitTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helper — creates a fully valid movie row via factory, then re-fetches it
    // as LikeableTestModel so all NOT NULL constraints are satisfied while the
    // test model still exposes the HasLike trait methods.
    // -------------------------------------------------------------------------

    private function makeTarget(): LikeableTestModel
    {
        $movie = Movie::factory()->create();

        return LikeableTestModel::findOrFail($movie->id);
    }

    // -------------------------------------------------------------------------
    // Relationship
    // -------------------------------------------------------------------------

    public function test_likes_returns_morph_many_of_the_model(): void
    {
        $owner  = User::factory()->create();
        $target = $this->makeTarget();

        Like::factory()->forUser($owner)->forLikeable($target, LikeableTestModel::class)->create();

        $this->assertCount(1, $target->likes);
        $this->assertSame($owner->id, $target->likes->first()->user_id);
    }

    // -------------------------------------------------------------------------
    // isLikedBy
    // -------------------------------------------------------------------------

    public function test_is_liked_by_returns_true_when_user_has_liked(): void
    {
        $user   = User::factory()->create();
        $target = $this->makeTarget();

        Like::factory()->forUser($user)->forLikeable($target, LikeableTestModel::class)->create();

        $this->assertTrue($target->isLikedBy($user->id));
    }

    public function test_is_liked_by_returns_false_when_user_has_not_liked(): void
    {
        $user   = User::factory()->create();
        $target = $this->makeTarget();

        $this->assertFalse($target->isLikedBy($user->id));
    }

    // -------------------------------------------------------------------------
    // isLiked (auth-aware)
    // -------------------------------------------------------------------------

    public function test_is_liked_returns_false_when_unauthenticated(): void
    {
        $target = $this->makeTarget();

        $this->assertFalse($target->isLiked());
    }

    public function test_is_liked_returns_true_for_authenticated_user_who_liked(): void
    {
        $user   = User::factory()->create();
        $target = $this->makeTarget();

        Like::factory()->forUser($user)->forLikeable($target, LikeableTestModel::class)->create();

        Auth::login($user);

        $this->assertTrue($target->isLiked());

        Auth::logout();
    }

    public function test_is_liked_returns_false_for_authenticated_user_who_has_not_liked(): void
    {
        $user   = User::factory()->create();
        $target = $this->makeTarget();

        Auth::login($user);

        $this->assertFalse($target->isLiked());

        Auth::logout();
    }

    // -------------------------------------------------------------------------
    // likesCount
    // -------------------------------------------------------------------------

    public function test_likes_count_returns_correct_total(): void
    {
        $target = $this->makeTarget();
        $users  = User::factory()->count(4)->create();

        foreach ($users as $user) {
            Like::factory()->forUser($user)->forLikeable($target, LikeableTestModel::class)->create();
        }

        $this->assertSame(4, $target->likesCount());
    }

    public function test_likes_count_returns_zero_when_no_likes(): void
    {
        $target = $this->makeTarget();

        $this->assertSame(0, $target->likesCount());
    }

    // -------------------------------------------------------------------------
    // toggleLikeBy
    // -------------------------------------------------------------------------

    public function test_toggle_like_by_adds_when_not_yet_liked(): void
    {
        $user   = User::factory()->create();
        $target = $this->makeTarget();

        $result = $target->toggleLikeBy($user->id);

        $this->assertTrue($result['liked']);
        $this->assertSame(1, $result['count']);
        $this->assertDatabaseHas('likes', [
            'user_id'       => $user->id,
            'likeable_id'   => $target->id,
            'likeable_type' => LikeableTestModel::class,
        ]);
    }

    public function test_toggle_like_by_removes_when_already_liked(): void
    {
        $user   = User::factory()->create();
        $target = $this->makeTarget();

        Like::factory()->forUser($user)->forLikeable($target, LikeableTestModel::class)->create();

        $result = $target->toggleLikeBy($user->id);

        $this->assertFalse($result['liked']);
        $this->assertSame(0, $result['count']);
        $this->assertDatabaseMissing('likes', [
            'user_id'       => $user->id,
            'likeable_id'   => $target->id,
            'likeable_type' => LikeableTestModel::class,
        ]);
    }

    public function test_double_toggle_leaves_no_likes(): void
    {
        $user   = User::factory()->create();
        $target = $this->makeTarget();

        $target->toggleLikeBy($user->id);
        $target->toggleLikeBy($user->id);

        $this->assertSame(0, $target->likesCount());
    }

    // -------------------------------------------------------------------------
    // Trait existence
    // -------------------------------------------------------------------------

    public function test_trait_is_available_for_use_on_models(): void
    {
        $this->assertTrue(trait_exists(HasLike::class));
    }

    public function test_likes_relationship_is_morph_many(): void
    {
        $target = $this->makeTarget();

        $this->assertInstanceOf(MorphMany::class, $target->likes());
    }
}
