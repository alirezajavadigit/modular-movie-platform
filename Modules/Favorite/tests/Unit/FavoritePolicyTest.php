<?php

namespace Modules\Favorite\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Modules\Favorite\Models\Favorite;
use Modules\Favorite\Policies\FavoritePolicy;
use Modules\Favorite\Tests\TestCase;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Modules\Person\Models\Person;

class FavoritePolicyTest extends TestCase
{
    use RefreshDatabase;

    private FavoritePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new FavoritePolicy();
    }

    public function test_view_any_returns_true_when_permission_is_not_seeded(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_view_returns_true_for_owner_when_permission_is_not_seeded(): void
    {
        $user     = User::factory()->create();
        $target   = Article::factory()->create();
        $favorite = Favorite::factory()->forUser($user)->forFavoriteable($target, 'article')->create();

        $this->assertTrue($this->policy->view($user, $favorite));
    }

    public function test_view_returns_false_for_non_owner_when_permission_is_not_seeded(): void
    {
        $owner    = User::factory()->create();
        $intruder = User::factory()->create();
        $target   = Movie::factory()->create();
        $favorite = Favorite::factory()->forUser($owner)->forFavoriteable($target, 'movie')->create();

        $this->assertFalse($this->policy->view($intruder, $favorite));
    }

    public function test_create_returns_true_when_permission_is_not_seeded(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->create($user));
    }

    public function test_delete_returns_true_for_owner_when_permission_is_not_seeded(): void
    {
        $user     = User::factory()->create();
        $target   = Episode::factory()->create();
        $favorite = Favorite::factory()->forUser($user)->forFavoriteable($target, 'episode')->create();

        $this->assertTrue($this->policy->delete($user, $favorite));
    }

    public function test_delete_returns_false_for_non_owner_when_permission_is_not_seeded(): void
    {
        $owner    = User::factory()->create();
        $intruder = User::factory()->create();
        $target   = Movie::factory()->create();
        $favorite = Favorite::factory()->forUser($owner)->forFavoriteable($target, 'movie')->create();

        $this->assertFalse($this->policy->delete($intruder, $favorite));
    }

    public function test_restore_returns_false_when_permission_is_not_seeded(): void
    {
        $user     = User::factory()->create();
        $target   = Article::factory()->create();
        $favorite = Favorite::factory()->forUser($user)->forFavoriteable($target, 'article')->create();

        $this->assertFalse($this->policy->restore($user, $favorite));
    }
}
