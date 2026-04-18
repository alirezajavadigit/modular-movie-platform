<?php

namespace Modules\Discussion\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\User;
use Modules\Discussion\Enums\DiscussionStatus;
use Modules\Discussion\Models\Discussion;
use Modules\Discussion\Repositories\DiscussionRepository;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\Relation;

class DiscussionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DiscussionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DiscussionRepository(new Discussion());
    }

    public function test_it_can_find_discussion_by_id(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();
        $discussion = Discussion::factory()->for($user)->for($movie, 'discussionable')->create();

        $result = $this->repository->findById($discussion->id);

        $this->assertInstanceOf(Discussion::class, $result);
        $this->assertEquals($discussion->id, $result->id);
    }

    public function test_it_returns_null_when_discussion_not_found(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function test_it_can_get_discussions_by_discussionable(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();

        Discussion::factory()->count(5)->for($user)->for($movie, 'discussionable')->create();
        Discussion::factory()->count(3)->for($user)->create([
            'discussionable_type' => Relation::getMorphAlias(Episode::class),
            'discussionable_id'   => 1,
        ]);

        $result = $this->repository->getByDiscussionable(Movie::class, $movie->id, 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->total());
        $this->assertEquals(10, $result->perPage());
    }

    public function test_it_can_get_approved_discussions_by_discussionable(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();

        Discussion::factory()->count(3)->approved()->for($user)->for($movie, 'discussionable')->create();
        Discussion::factory()->count(2)->rejected()->for($user)->for($movie, 'discussionable')->create();
        Discussion::factory()->count(1)->pending()->for($user)->for($movie, 'discussionable')->create();

        $result = $this->repository->getApprovedByDiscussionable(Movie::class, $movie->id, 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->total());
        $result->each(function ($discussion) {
            $this->assertEquals(DiscussionStatus::APPROVED, $discussion->status);
        });
    }

    public function test_it_can_get_replies(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();

        $parent = Discussion::factory()->approved()->for($user)->for($movie, 'discussionable')->create();

        Discussion::factory()->count(3)->approved()->for($user)->for($movie, 'discussionable')
            ->create(['parent_id' => $parent->id]);
        Discussion::factory()->count(2)->rejected()->for($user)->for($movie, 'discussionable')
            ->create(['parent_id' => $parent->id]);
        Discussion::factory()->count(1)->pending()->for($user)->for($movie, 'discussionable')
            ->create(['parent_id' => $parent->id]);

        $result = $this->repository->getReplies($parent->id);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(3, $result->count());
        $result->each(function ($discussion) use ($parent) {
            $this->assertEquals($parent->id, $discussion->parent_id);
            $this->assertEquals(DiscussionStatus::APPROVED, $discussion->status);
        });
    }

    public function test_it_can_get_discussions_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $movie = Movie::factory()->create();

        Discussion::factory()->count(4)->for($user1)->for($movie, 'discussionable')->create();
        Discussion::factory()->count(2)->for($user2)->for($movie, 'discussionable')->create();

        $result = $this->repository->getByUser($user1->id, 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(4, $result->total());
        $result->each(function ($discussion) use ($user1) {
            $this->assertEquals($user1->id, $discussion->user_id);
        });
    }

    public function test_it_can_get_pending_discussions(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();

        Discussion::factory()->count(3)->pending()->for($user)->for($movie, 'discussionable')->create();
        Discussion::factory()->count(2)->approved()->for($user)->for($movie, 'discussionable')->create();
        Discussion::factory()->count(1)->rejected()->for($user)->for($movie, 'discussionable')->create();

        $result = $this->repository->getPending(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->total());
        $result->each(function ($discussion) {
            $this->assertEquals(DiscussionStatus::PENDING, $discussion->status);
        });
    }

    public function test_it_can_create_discussion(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();

        $data = [
            'user_id'             => $user->id,
            'discussionable_id'   => $movie->id,
            'discussionable_type' => Movie::class,
            'body'                => 'Test discussion body',
            'status'              => DiscussionStatus::PENDING->value,
            'ip_address'          => '127.0.0.1',
        ];

        $result = $this->repository->create($data);

        $this->assertInstanceOf(Discussion::class, $result);
        $this->assertEquals($data['body'], $result->body);
        $this->assertDatabaseHas('discussions', $data);
    }

    public function test_it_can_update_discussion(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();
        $discussion = Discussion::factory()->for($user)->for($movie, 'discussionable')->create();

        $result = $this->repository->update($discussion, [
            'body'   => 'Updated body',
            'status' => DiscussionStatus::APPROVED->value,
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('discussions', [
            'id'     => $discussion->id,
            'body'   => 'Updated body',
            'status' => DiscussionStatus::APPROVED->value,
        ]);
    }

    public function test_it_can_soft_delete_discussion(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();
        $discussion = Discussion::factory()->for($user)->for($movie, 'discussionable')->create();

        $result = $this->repository->delete($discussion);

        $this->assertTrue($result);
        $this->assertSoftDeleted('discussions', ['id' => $discussion->id]);
    }

    public function test_it_can_force_delete_discussion(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();
        $discussion = Discussion::factory()->for($user)->for($movie, 'discussionable')->create();
        $discussion->delete();

        $result = $this->repository->forceDelete($discussion);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('discussions', ['id' => $discussion->id]);
    }

    public function test_it_can_restore_discussion(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();
        $discussion = Discussion::factory()->for($user)->for($movie, 'discussionable')->create();
        $discussion->delete();

        $result = $this->repository->restore($discussion);

        $this->assertTrue($result);
        $this->assertDatabaseHas('discussions', [
            'id'         => $discussion->id,
            'deleted_at' => null,
        ]);
    }

    public function test_it_can_count_discussions_by_discussionable(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();

        Discussion::factory()->count(5)->for($user)->for($movie, 'discussionable')->create();

        $this->assertEquals(5, $this->repository->countByDiscussionable(Movie::class, $movie->id));
    }

    public function test_it_can_count_approved_discussions_by_discussionable(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();

        Discussion::factory()->count(3)->approved()->for($user)->for($movie, 'discussionable')->create();
        Discussion::factory()->count(2)->pending()->for($user)->for($movie, 'discussionable')->create();

        $this->assertEquals(3, $this->repository->countApprovedByDiscussionable(Movie::class, $movie->id));
    }
}
