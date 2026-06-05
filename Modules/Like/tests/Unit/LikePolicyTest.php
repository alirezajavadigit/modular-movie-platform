<?php

namespace Modules\Like\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Modules\Like\Models\Like;
use Modules\Like\Policies\LikePolicy;
use Modules\Like\Tests\TestCase;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;

class LikePolicyTest extends TestCase
{
    use RefreshDatabase;

    private LikePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new LikePolicy();
    }

    public function test_view_any_returns_true_when_permission_is_not_seeded(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_view_returns_true_for_owner_when_permission_is_not_seeded(): void
    {
        $user   = User::factory()->create();
        $target = Article::factory()->create();
        $like   = Like::factory()->forUser($user)->forLikeable($target, 'article')->create();

        $this->assertTrue($this->policy->view($user, $like));
    }

    public function test_view_returns_false_for_non_owner_when_permission_is_not_seeded(): void
    {
        $owner    = User::factory()->create();
        $intruder = User::factory()->create();
        $target   = Movie::factory()->create();
        $like     = Like::factory()->forUser($owner)->forLikeable($target, 'movie')->create();

        $this->assertFalse($this->policy->view($intruder, $like));
    }

    public function test_create_returns_true_when_permission_is_not_seeded(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->create($user));
    }

    public function test_delete_returns_true_for_owner_when_permission_is_not_seeded(): void
    {
        $user   = User::factory()->create();
        $target = Episode::factory()->create();
        $like   = Like::factory()->forUser($user)->forLikeable($target, 'episode')->create();

        $this->assertTrue($this->policy->delete($user, $like));
    }

    public function test_delete_returns_false_for_non_owner_when_permission_is_not_seeded(): void
    {
        $owner    = User::factory()->create();
        $intruder = User::factory()->create();
        $target   = Movie::factory()->create();
        $like     = Like::factory()->forUser($owner)->forLikeable($target, 'movie')->create();

        $this->assertFalse($this->policy->delete($intruder, $like));
    }

    public function test_restore_returns_false_when_permission_is_not_seeded(): void
    {
        $user   = User::factory()->create();
        $target = Article::factory()->create();
        $like   = Like::factory()->forUser($user)->forLikeable($target, 'article')->create();

        $this->assertFalse($this->policy->restore($user, $like));
    }
}
