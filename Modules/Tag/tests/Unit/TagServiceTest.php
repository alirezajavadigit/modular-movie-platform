<?php

namespace Modules\Tag\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Mockery;
use Modules\Tag\Contracts\TagRepositoryInterface;
use Modules\Tag\DTOs\CreateTagDTO;
use Modules\Tag\DTOs\UpdateTagDTO;
use Modules\Tag\Models\Tag;
use Modules\Tag\Services\TagService;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class TagServiceTest extends TestCase
{
    private TagRepositoryInterface $repository;
    private TagService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(TagRepositoryInterface::class);
        $this->service    = new TagService($this->repository);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    private function makeTag(array $attributes = []): Tag
    {
        $tag = Mockery::mock(Tag::class)->makePartial();

        foreach (
            array_merge([
                'id'        => 1,
                'color'     => '#ff0000',
                'is_active' => true,
            ], $attributes) as $key => $value
        ) {
            $tag->$key = $value;
        }

        return $tag;
    }

    private function makePaginator(array $items = []): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, count($items), 15);
    }

    private function makeCreateDTO(array $override = []): CreateTagDTO
    {
        $data = array_merge([
            'name'        => ['en' => 'Test Tag'],
            'slug'        => ['en' => 'test-tag'],
            'description' => null,
            'color'       => '#ff0000',
            'isActive'    => true,
        ], $override);

        return new CreateTagDTO(
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'],
            color: $data['color'],
            isActive: $data['isActive'],
        );
    }

    private function makeUpdateDTO(array $override = []): UpdateTagDTO
    {
        $data = array_merge([
            'name'        => ['en' => 'Updated Tag'],
            'slug'        => null,
            'description' => null,
            'color'       => null,
            'isActive'    => null,
        ], $override);

        return new UpdateTagDTO(
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'],
            color: $data['color'],
            isActive: $data['isActive'],
        );
    }


    public function find_by_id_returns_tag(): void
    {
        $tag = $this->makeTag();
        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($tag);

        $this->assertSame($tag, $this->service->findById(1));
    }


    public function find_by_id_throws_on_invalid_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->findById(0);
    }


    public function find_by_slug_returns_tag(): void
    {
        $tag = $this->makeTag();
        $this->repository->shouldReceive('findBySlug')->once()->with('laravel')->andReturn($tag);

        $this->assertSame($tag, $this->service->findBySlug('laravel'));
    }


    public function find_by_slug_throws_on_empty_slug(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->findBySlug('  ');
    }


    public function get_all_returns_collection(): void
    {
        $collection = new Collection([$this->makeTag()]);
        $this->repository->shouldReceive('getAll')->once()->andReturn($collection);

        $this->assertSame($collection, $this->service->getAll());
    }


    public function paginate_returns_paginated_result(): void
    {
        $paginator = $this->makePaginator();
        $this->repository->shouldReceive('paginate')->once()->with(15)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->paginate());
    }


    public function paginate_throws_on_invalid_per_page(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->paginate(0);
    }


    public function paginate_throws_when_per_page_exceeds_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->paginate(101);
    }


    public function get_active_returns_paginated_result(): void
    {
        $paginator = $this->makePaginator();
        $this->repository->shouldReceive('getActive')->once()->with(15)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->getActive());
    }


    public function get_popular_returns_collection(): void
    {
        $collection = new Collection([$this->makeTag()]);
        $this->repository->shouldReceive('getPopular')->once()->with(10)->andReturn($collection);

        $this->assertSame($collection, $this->service->getPopular());
    }


    public function get_popular_throws_on_invalid_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getPopular(0);
    }


    public function get_popular_throws_when_limit_exceeds_max(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getPopular(101);
    }


    public function search_returns_paginated_result(): void
    {
        $paginator = $this->makePaginator();
        $this->repository->shouldReceive('search')->once()->with('laravel', 15)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->search('laravel'));
    }


    public function search_throws_on_empty_query(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->search('  ');
    }


    public function search_throws_on_short_query(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->search('a');
    }


    public function store_creates_tag_successfully(): void
    {
        $dto = $this->makeCreateDTO();
        $tag = $this->makeTag();

        $this->repository->shouldReceive('findBySlug')->once()->with('test-tag')->andReturn(null);
        $this->repository->shouldReceive('create')->once()->with($dto)->andReturn($tag);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());
        $tag->shouldReceive('refresh')->once()->andReturnSelf();

        $this->assertSame($tag, $this->service->store($dto));
    }


    public function store_throws_on_empty_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->store($this->makeCreateDTO(['name' => []]));
    }


    public function store_throws_on_empty_slug(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->store($this->makeCreateDTO(['slug' => []]));
    }


    public function store_throws_when_slug_already_exists(): void
    {
        $this->expectException(LogicException::class);

        $existing = $this->makeTag();
        $this->repository->shouldReceive('findBySlug')->once()->andReturn($existing);

        $this->service->store($this->makeCreateDTO());
    }


    public function update_modifies_tag_successfully(): void
    {
        $dto = $this->makeUpdateDTO();
        $tag = $this->makeTag();

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($tag);
        $this->repository->shouldReceive('update')->once()->with(1, $dto)->andReturn($tag);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());
        $tag->shouldReceive('refresh')->once()->andReturnSelf();

        $this->assertSame($tag, $this->service->update(1, $dto));
    }


    public function update_throws_on_invalid_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->update(0, $this->makeUpdateDTO());
    }


    public function update_throws_when_tag_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->repository->shouldReceive('findById')->once()->andReturn(null);

        $this->service->update(1, $this->makeUpdateDTO());
    }


    public function update_throws_when_slug_taken_by_another_tag(): void
    {
        $this->expectException(LogicException::class);

        $tag   = $this->makeTag(['id' => 1]);
        $other = $this->makeTag(['id' => 2]);

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($tag);
        $this->repository->shouldReceive('findBySlug')->once()->andReturn($other);

        $this->service->update(1, $this->makeUpdateDTO(['slug' => ['en' => 'taken-slug']]));
    }


    public function delete_removes_tag_successfully(): void
    {
        $tag = $this->makeTag();

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($tag);
        $this->repository->shouldReceive('delete')->once()->with(1)->andReturn(true);

        $this->assertTrue($this->service->delete(1));
    }


    public function delete_throws_on_invalid_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->delete(0);
    }


    public function delete_throws_when_tag_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->shouldReceive('findById')->once()->andReturn(null);
        $this->service->delete(1);
    }


    public function delete_throws_runtime_when_repository_fails(): void
    {
        $this->expectException(RuntimeException::class);

        $tag = $this->makeTag();
        $this->repository->shouldReceive('findById')->once()->andReturn($tag);
        $this->repository->shouldReceive('delete')->once()->andReturn(false);

        $this->service->delete(1);
    }


    public function force_delete_removes_permanently(): void
    {
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());
        $this->repository->shouldReceive('forceDelete')->once()->with(1)->andReturn(true);

        $this->assertTrue($this->service->forceDelete(1));
    }


    public function force_delete_throws_on_invalid_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->forceDelete(0);
    }


    public function restore_returns_restored_tag(): void
    {
        $tag = $this->makeTag();
        $this->repository->shouldReceive('restore')->once()->with(1)->andReturn($tag);

        $this->assertSame($tag, $this->service->restore(1));
    }


    public function restore_throws_on_invalid_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->restore(0);
    }


    public function get_trashed_returns_paginated_result(): void
    {
        $paginator = $this->makePaginator();
        $this->repository->shouldReceive('getTrashed')->once()->with(15)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->getTrashed());
    }


    public function activate_changes_status_to_active(): void
    {
        $tag = $this->makeTag(['is_active' => false]);
        $activated = $this->makeTag(['is_active' => true]);

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($tag);
        $this->repository->shouldReceive('activate')->once()->with(1)->andReturn($activated);

        $this->assertSame($activated, $this->service->activate(1));
    }


    public function activate_throws_when_already_active(): void
    {
        $this->expectException(LogicException::class);

        $tag = $this->makeTag(['is_active' => true]);
        $this->repository->shouldReceive('findById')->once()->andReturn($tag);

        $this->service->activate(1);
    }


    public function activate_throws_when_tag_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->shouldReceive('findById')->once()->andReturn(null);
        $this->service->activate(1);
    }


    public function deactivate_changes_status_to_inactive(): void
    {
        $tag = $this->makeTag(['is_active' => true]);
        $deactivated = $this->makeTag(['is_active' => false]);

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($tag);
        $this->repository->shouldReceive('deactivate')->once()->with(1)->andReturn($deactivated);

        $this->assertSame($deactivated, $this->service->deactivate(1));
    }


    public function deactivate_throws_when_already_inactive(): void
    {
        $this->expectException(LogicException::class);

        $tag = $this->makeTag(['is_active' => false]);
        $this->repository->shouldReceive('findById')->once()->andReturn($tag);

        $this->service->deactivate(1);
    }
}
