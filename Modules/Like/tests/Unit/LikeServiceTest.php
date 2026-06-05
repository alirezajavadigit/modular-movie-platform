<?php

namespace Modules\Like\Tests\Unit;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Modules\Like\Contracts\LikeRepositoryInterface;
use Modules\Like\DTOs\CreateLikeDTO;
use Modules\Like\Models\Like;
use Modules\Like\Services\LikeService;
use Modules\Like\Tests\TestCase;

class LikeServiceTest extends TestCase
{
    private LikeService   $service;
    private MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(LikeRepositoryInterface::class);
        $this->service    = new LikeService($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_user_likes_delegates_to_repository(): void
    {
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $this->repository
            ->shouldReceive('getByUser')
            ->once()
            ->with(1, 10)
            ->andReturn($paginator);

        $this->assertSame($paginator, $this->service->getUserLikes(1, 10));
    }

    public function test_store_maps_dto_to_repository_create(): void
    {
        $dto  = new CreateLikeDTO(userId: 1, likeableId: 5, likeableType: 'App\\Models\\Movie');
        $like = new Like();

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with([
                'user_id'       => 1,
                'likeable_id'   => 5,
                'likeable_type' => 'App\\Models\\Movie',
            ])
            ->andReturn($like);

        $this->assertSame($like, $this->service->store($dto));
    }

    public function test_delete_delegates_to_repository(): void
    {
        $like = new Like();

        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with($like)
            ->andReturn(true);

        $this->assertTrue($this->service->delete($like));
    }

    public function test_is_liked_delegates_to_repository_exists_check(): void
    {
        $this->repository
            ->shouldReceive('existsByUserAndLikeable')
            ->once()
            ->with(1, 'App\\Models\\Movie', 5)
            ->andReturn(true);

        $this->assertTrue($this->service->isLiked(1, 'App\\Models\\Movie', 5));
    }

    public function test_find_existing_delegates_to_repository(): void
    {
        $like = new Like();

        $this->repository
            ->shouldReceive('findByUserAndLikeable')
            ->once()
            ->with(1, 'App\\Models\\Movie', 5)
            ->andReturn($like);

        $this->assertSame($like, $this->service->findExisting(1, 'App\\Models\\Movie', 5));
    }

    public function test_toggle_creates_like_when_it_does_not_exist(): void
    {
        $dto  = new CreateLikeDTO(userId: 1, likeableId: 5, likeableType: 'App\\Models\\Movie');
        $like = new Like();

        $this->repository->shouldReceive('findByUserAndLikeable')->once()->andReturn(null);
        $this->repository->shouldReceive('create')->once()->andReturn($like);
        $this->repository->shouldReceive('countByLikeable')->once()->with('App\\Models\\Movie', 5)->andReturn(3);

        $result = $this->service->toggle($dto);

        $this->assertTrue($result['liked']);
        $this->assertSame(3, $result['count']);
    }

    public function test_toggle_deletes_like_when_it_already_exists(): void
    {
        $dto      = new CreateLikeDTO(userId: 1, likeableId: 5, likeableType: 'App\\Models\\Movie');
        $existing = new Like();

        $this->repository->shouldReceive('findByUserAndLikeable')->once()->andReturn($existing);
        $this->repository->shouldReceive('delete')->once()->with($existing)->andReturn(true);
        $this->repository->shouldReceive('countByLikeable')->once()->with('App\\Models\\Movie', 5)->andReturn(2);

        $result = $this->service->toggle($dto);

        $this->assertFalse($result['liked']);
        $this->assertSame(2, $result['count']);
    }

    public function test_toggle_returns_array_with_liked_and_count_keys(): void
    {
        $dto  = new CreateLikeDTO(userId: 1, likeableId: 5, likeableType: 'App\\Models\\Movie');
        $like = new Like();

        $this->repository->shouldReceive('findByUserAndLikeable')->once()->andReturn(null);
        $this->repository->shouldReceive('create')->once()->andReturn($like);
        $this->repository->shouldReceive('countByLikeable')->once()->andReturn(1);

        $result = $this->service->toggle($dto);

        $this->assertArrayHasKey('liked', $result);
        $this->assertArrayHasKey('count', $result);
    }
}
