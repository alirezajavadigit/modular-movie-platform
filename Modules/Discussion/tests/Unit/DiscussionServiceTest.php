<?php

namespace Modules\Discussion\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Modules\Discussion\Contracts\DiscussionRepositoryInterface;
use Modules\Discussion\DTOs\CreateDiscussionDTO;
use Modules\Discussion\DTOs\UpdateDiscussionDTO;
use Modules\Discussion\Enums\DiscussionStatus;
use Modules\Discussion\Models\Discussion;
use Modules\Discussion\Services\DiscussionService;
use Tests\TestCase;

class DiscussionServiceTest extends TestCase
{
    private $repository;
    private DiscussionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(DiscussionRepositoryInterface::class);
        $this->service = new DiscussionService($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_can_find_discussion_by_id(): void
    {
        $discussion = Mockery::mock(Discussion::class);

        $this->repository->shouldReceive('findById')->with(1)->once()->andReturn($discussion);

        $this->assertSame($discussion, $this->service->findById(1));
    }

    public function test_it_can_get_discussions_by_discussionable(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 15);

        $this->repository->shouldReceive('getByDiscussionable')
            ->with('Modules\\Movie\\Models\\Movie', 1, 15)->once()->andReturn($paginator);

        $this->assertInstanceOf(
            LengthAwarePaginator::class,
            $this->service->getByDiscussionable('Modules\\Movie\\Models\\Movie', 1, 15)
        );
    }

    public function test_it_can_get_approved_discussions_by_discussionable(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 15);

        $this->repository->shouldReceive('getApprovedByDiscussionable')
            ->with('Modules\\Movie\\Models\\Movie', 1, 15)->once()->andReturn($paginator);

        $this->assertInstanceOf(
            LengthAwarePaginator::class,
            $this->service->getApprovedByDiscussionable('Modules\\Movie\\Models\\Movie', 1, 15)
        );
    }

    public function test_it_can_get_replies(): void
    {
        $collection = new Collection([]);

        $this->repository->shouldReceive('getReplies')->with(5)->once()->andReturn($collection);

        $this->assertInstanceOf(Collection::class, $this->service->getReplies(5));
    }

    public function test_it_can_get_discussions_by_user(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 15);

        $this->repository->shouldReceive('getByUser')->with(1, 15)->once()->andReturn($paginator);

        $this->assertInstanceOf(LengthAwarePaginator::class, $this->service->getByUser(1, 15));
    }

    public function test_it_can_get_pending_discussions(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 15);

        $this->repository->shouldReceive('getPending')->with(15)->once()->andReturn($paginator);

        $this->assertInstanceOf(LengthAwarePaginator::class, $this->service->getPending(15));
    }

    public function test_it_can_store_discussion(): void
    {
        $dto = new CreateDiscussionDTO(
            userId: 1,
            discussionableId: 10,
            discussionableType: 'Modules\\Movie\\Models\\Movie',
            body: 'Test discussion body',
            parentId: null,
            status: DiscussionStatus::PENDING,
            ipAddress: '127.0.0.1',
        );

        $discussion = Mockery::mock(Discussion::class);

        $this->repository->shouldReceive('create')
            ->with([
                'user_id'             => 1,
                'discussionable_id'   => 10,
                'discussionable_type' => 'Modules\\Movie\\Models\\Movie',
                'body'                => 'Test discussion body',
                'parent_id'           => null,
                'status'              => DiscussionStatus::PENDING->value,
                'ip_address'          => '127.0.0.1',
            ])
            ->once()->andReturn($discussion);

        $this->assertSame($discussion, $this->service->store($dto));
    }

    public function test_it_can_update_discussion(): void
    {
        $dto = new UpdateDiscussionDTO(body: 'Updated body', status: DiscussionStatus::APPROVED);
        $discussion = Mockery::mock(Discussion::class);

        $this->repository->shouldReceive('update')
            ->with($discussion, [
                'body'   => 'Updated body',
                'status' => DiscussionStatus::APPROVED->value,
            ])->once()->andReturn(true);

        $this->assertTrue($this->service->update($discussion, $dto));
    }

    public function test_update_short_circuits_on_empty_dto(): void
    {
        $dto = new UpdateDiscussionDTO();
        $discussion = Mockery::mock(Discussion::class);

        $this->repository->shouldNotReceive('update');

        $this->assertTrue($this->service->update($discussion, $dto));
    }

    public function test_it_can_delete_discussion(): void
    {
        $discussion = Mockery::mock(Discussion::class);
        $this->repository->shouldReceive('delete')->with($discussion)->once()->andReturn(true);

        $this->assertTrue($this->service->delete($discussion));
    }

    public function test_it_can_force_delete_discussion(): void
    {
        $discussion = Mockery::mock(Discussion::class);
        $this->repository->shouldReceive('forceDelete')->with($discussion)->once()->andReturn(true);

        $this->assertTrue($this->service->forceDelete($discussion));
    }

    public function test_it_can_restore_discussion(): void
    {
        $discussion = Mockery::mock(Discussion::class);
        $this->repository->shouldReceive('restore')->with($discussion)->once()->andReturn(true);

        $this->assertTrue($this->service->restore($discussion));
    }

    public function test_it_can_approve_discussion(): void
    {
        $discussion = Mockery::mock(Discussion::class);

        $this->repository->shouldReceive('update')
            ->with($discussion, ['status' => DiscussionStatus::APPROVED->value])
            ->once()->andReturn(true);

        $this->assertTrue($this->service->approve($discussion));
    }

    public function test_it_can_reject_discussion(): void
    {
        $discussion = Mockery::mock(Discussion::class);

        $this->repository->shouldReceive('update')
            ->with($discussion, ['status' => DiscussionStatus::REJECTED->value])
            ->once()->andReturn(true);

        $this->assertTrue($this->service->reject($discussion));
    }

    public function test_it_can_mark_discussion_as_pending(): void
    {
        $discussion = Mockery::mock(Discussion::class);

        $this->repository->shouldReceive('update')
            ->with($discussion, ['status' => DiscussionStatus::PENDING->value])
            ->once()->andReturn(true);

        $this->assertTrue($this->service->markAsPending($discussion));
    }

    public function test_it_can_get_discussions_count(): void
    {
        $this->repository->shouldReceive('countByDiscussionable')
            ->with('Modules\\Movie\\Models\\Movie', 1)->once()->andReturn(10);

        $this->assertEquals(10, $this->service->discussionsCount('Modules\\Movie\\Models\\Movie', 1));
    }

    public function test_it_can_get_approved_discussions_count(): void
    {
        $this->repository->shouldReceive('countApprovedByDiscussionable')
            ->with('Modules\\Movie\\Models\\Movie', 1)->once()->andReturn(5);

        $this->assertEquals(5, $this->service->approvedDiscussionsCount('Modules\\Movie\\Models\\Movie', 1));
    }

    public function test_it_can_check_if_has_discussions(): void
    {
        $this->repository->shouldReceive('countByDiscussionable')
            ->with('Modules\\Movie\\Models\\Movie', 1)->once()->andReturn(3);

        $this->assertTrue($this->service->hasDiscussions('Modules\\Movie\\Models\\Movie', 1));
    }

    public function test_it_returns_false_when_has_no_discussions(): void
    {
        $this->repository->shouldReceive('countByDiscussionable')
            ->with('Modules\\Movie\\Models\\Movie', 1)->once()->andReturn(0);

        $this->assertFalse($this->service->hasDiscussions('Modules\\Movie\\Models\\Movie', 1));
    }
}
