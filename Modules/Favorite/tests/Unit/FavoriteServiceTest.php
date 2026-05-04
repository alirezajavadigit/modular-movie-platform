<?php

namespace Modules\Favorite\Tests\Unit;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Modules\Favorite\Contracts\FavoriteRepositoryInterface;
use Modules\Favorite\DTOs\CreateFavoriteDTO;
use Modules\Favorite\Models\Favorite;
use Modules\Favorite\Services\FavoriteService;
use Modules\Favorite\Tests\TestCase;

class FavoriteServiceTest extends TestCase
{
    private FavoriteService $service;
    private MockInterface   $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(FavoriteRepositoryInterface::class);
        $this->service    = new FavoriteService($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_user_favorites_delegates_to_repository(): void
    {
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $this->repository
            ->shouldReceive('getByUser')
            ->once()
            ->with(1, 10)
            ->andReturn($paginator);

        $this->assertSame($paginator, $this->service->getUserFavorites(1, 10));
    }

    public function test_store_maps_dto_to_repository_create(): void
    {
        $dto      = new CreateFavoriteDTO(userId: 1, favoriteableId: 5, favoriteableType: 'App\\Models\\Movie');
        $favorite = new Favorite();

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with([
                'user_id'           => 1,
                'favoriteable_id'   => 5,
                'favoriteable_type' => 'App\\Models\\Movie',
            ])
            ->andReturn($favorite);

        $this->assertSame($favorite, $this->service->store($dto));
    }

    public function test_delete_delegates_to_repository(): void
    {
        $favorite = new Favorite();

        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with($favorite)
            ->andReturn(true);

        $this->assertTrue($this->service->delete($favorite));
    }

    public function test_is_favorited_delegates_to_repository_exists_check(): void
    {
        $this->repository
            ->shouldReceive('existsByUserAndFavoriteable')
            ->once()
            ->with(1, 'App\\Models\\Movie', 5)
            ->andReturn(true);

        $this->assertTrue($this->service->isFavorited(1, 'App\\Models\\Movie', 5));
    }

    public function test_find_existing_delegates_to_repository(): void
    {
        $favorite = new Favorite();

        $this->repository
            ->shouldReceive('findByUserAndFavoriteable')
            ->once()
            ->with(1, 'App\\Models\\Movie', 5)
            ->andReturn($favorite);

        $this->assertSame($favorite, $this->service->findExisting(1, 'App\\Models\\Movie', 5));
    }

    public function test_toggle_creates_favorite_when_it_does_not_exist(): void
    {
        $dto      = new CreateFavoriteDTO(userId: 1, favoriteableId: 5, favoriteableType: 'App\\Models\\Movie');
        $favorite = new Favorite();

        $this->repository->shouldReceive('findByUserAndFavoriteable')->once()->andReturn(null);
        $this->repository->shouldReceive('create')->once()->andReturn($favorite);
        $this->repository->shouldReceive('countByFavoriteable')->once()->with('App\\Models\\Movie', 5)->andReturn(3);

        $result = $this->service->toggle($dto);

        $this->assertTrue($result['favorited']);
        $this->assertSame(3, $result['count']);
    }

    public function test_toggle_deletes_favorite_when_it_already_exists(): void
    {
        $dto      = new CreateFavoriteDTO(userId: 1, favoriteableId: 5, favoriteableType: 'App\\Models\\Movie');
        $existing = new Favorite();

        $this->repository->shouldReceive('findByUserAndFavoriteable')->once()->andReturn($existing);
        $this->repository->shouldReceive('delete')->once()->with($existing)->andReturn(true);
        $this->repository->shouldReceive('countByFavoriteable')->once()->with('App\\Models\\Movie', 5)->andReturn(2);

        $result = $this->service->toggle($dto);

        $this->assertFalse($result['favorited']);
        $this->assertSame(2, $result['count']);
    }
}
