<?php

namespace Modules\Category\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Mockery;
use Modules\Category\Contracts\CategoryRepositoryInterface;
use Modules\Category\DTOs\CreateCategoryDTO;
use Modules\Category\DTOs\UpdateCategoryDTO;
use Modules\Category\Models\Category;
use Modules\Category\Services\CategoryService;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    private CategoryRepositoryInterface $repository;
    private CategoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(CategoryRepositoryInterface::class);
        $this->service    = new CategoryService($this->repository);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    private function makeCategory(array $attributes = []): Category
    {
        $category = Mockery::mock(Category::class)->makePartial();

        foreach (
            array_merge([
                'id'        => 1,
                'parent_id' => null,
                'is_active' => true,
                'order'     => 0,
            ], $attributes) as $key => $value
        ) {
            $category->$key = $value;
        }

        return $category;
    }

    private function makePaginator(array $items = []): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, count($items), 15);
    }

    private function makeCreateDTO(array $override = []): CreateCategoryDTO
    {
        $data = array_merge([
            'name'        => ['en' => 'Test Category'],
            'slug'        => ['en' => 'test-category'],
            'description' => null,
            'parentId'    => null,
            'isActive'    => true,
            'order'       => 0,
        ], $override);

        return new CreateCategoryDTO(
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'],
            parentId: $data['parentId'],
            isActive: $data['isActive'],
            order: $data['order'],
        );
    }

    private function makeUpdateDTO(array $override = []): UpdateCategoryDTO
    {
        $data = array_merge([
            'name'        => ['en' => 'Updated Category'],
            'slug'        => null,
            'description' => null,
            'parentId'    => null,
            'isActive'    => null,
            'order'       => null,
        ], $override);

        return new UpdateCategoryDTO(
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'],
            parentId: $data['parentId'],
            isActive: $data['isActive'],
            order: $data['order'],
        );
    }


    public function find_by_id_returns_category(): void
    {
        $category = $this->makeCategory();

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($category);

        $this->assertSame($category, $this->service->findById(1));
    }


    public function find_by_id_throws_on_invalid_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->findById(0);
    }


    public function find_by_slug_returns_category(): void
    {
        $category = $this->makeCategory();

        $this->repository->shouldReceive('findBySlug')->once()->with('tech')->andReturn($category);

        $this->assertSame($category, $this->service->findBySlug('tech'));
    }


    public function find_by_slug_throws_on_empty_slug(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->findBySlug('   ');
    }


    public function get_all_returns_collection(): void
    {
        $collection = new Collection([$this->makeCategory()]);

        $this->repository->shouldReceive('getAll')->once()->andReturn($collection);

        $this->assertSame($collection, $this->service->getAll());
    }


    public function paginate_returns_paginated_result(): void
    {
        $paginator = $this->makePaginator([$this->makeCategory()]);

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


    public function get_by_parent_returns_paginated_result(): void
    {
        $paginator = $this->makePaginator();
        $this->repository->shouldReceive('getByParent')->once()->with(5, 15)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->getByParent(5));
    }


    public function get_by_parent_accepts_null_parent_id(): void
    {
        $paginator = $this->makePaginator();
        $this->repository->shouldReceive('getByParent')->once()->with(null, 15)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->getByParent(null));
    }


    public function get_by_parent_throws_on_negative_parent_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getByParent(-1);
    }


    public function get_tree_returns_collection(): void
    {
        $collection = new Collection([$this->makeCategory()]);
        $this->repository->shouldReceive('getTree')->once()->andReturn($collection);

        $this->assertSame($collection, $this->service->getTree());
    }


    public function search_returns_paginated_result(): void
    {
        $paginator = $this->makePaginator();
        $this->repository->shouldReceive('search')->once()->with('tech', 15)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->search('tech'));
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


    public function store_creates_category_successfully(): void
    {
        $dto = $this->makeCreateDTO();
        $category = $this->makeCategory();

        $this->repository->shouldReceive('findBySlug')->once()->with('test-category')->andReturn(null);
        $this->repository->shouldReceive('create')->once()->with($dto)->andReturn($category);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        $category->shouldReceive('refresh')->once()->andReturnSelf();

        $this->assertSame($category, $this->service->store($dto));
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

        $existing = $this->makeCategory();
        $this->repository->shouldReceive('findBySlug')->once()->andReturn($existing);

        $this->service->store($this->makeCreateDTO());
    }


    public function store_throws_when_parent_id_does_not_exist(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->repository->shouldReceive('findBySlug')->once()->andReturn(null);
        $this->repository->shouldReceive('findById')->once()->with(99)->andReturn(null);

        $this->service->store($this->makeCreateDTO(['parentId' => 99]));
    }


    public function update_modifies_category_successfully(): void
    {
        $dto = $this->makeUpdateDTO();
        $category = $this->makeCategory();

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($category);
        $this->repository->shouldReceive('update')->once()->with(1, $dto)->andReturn($category);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());
        $category->shouldReceive('refresh')->once()->andReturnSelf();

        $this->assertSame($category, $this->service->update(1, $dto));
    }


    public function update_throws_on_invalid_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->update(0, $this->makeUpdateDTO());
    }


    public function update_throws_when_category_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn(null);

        $this->service->update(1, $this->makeUpdateDTO());
    }


    public function update_throws_when_category_tries_to_be_its_own_parent(): void
    {
        $this->expectException(LogicException::class);

        $category = $this->makeCategory(['id' => 5]);
        $this->repository->shouldReceive('findById')->once()->with(5)->andReturn($category);

        $this->service->update(5, $this->makeUpdateDTO(['parentId' => 5]));
    }


    public function update_throws_when_slug_taken_by_another_category(): void
    {
        $this->expectException(LogicException::class);

        $category = $this->makeCategory(['id' => 1]);
        $other    = $this->makeCategory(['id' => 2]);

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($category);
        $this->repository->shouldReceive('findBySlug')->once()->andReturn($other);

        $this->service->update(1, $this->makeUpdateDTO(['slug' => ['en' => 'taken-slug']]));
    }


    public function delete_removes_category_successfully(): void
    {
        $category = $this->makeCategory();

        $childrenRelation = Mockery::mock();
        $childrenRelation->shouldReceive('exists')->once()->andReturn(false);
        $category->shouldReceive('children')->once()->andReturn($childrenRelation);

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($category);
        $this->repository->shouldReceive('delete')->once()->with(1)->andReturn(true);

        $this->assertTrue($this->service->delete(1));
    }


    public function delete_throws_when_category_has_children(): void
    {
        $this->expectException(LogicException::class);

        $category = $this->makeCategory();

        $childrenRelation = Mockery::mock();
        $childrenRelation->shouldReceive('exists')->once()->andReturn(true);
        $category->shouldReceive('children')->once()->andReturn($childrenRelation);

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($category);

        $this->service->delete(1);
    }


    public function delete_throws_on_invalid_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->delete(0);
    }


    public function delete_throws_when_category_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->shouldReceive('findById')->once()->andReturn(null);
        $this->service->delete(1);
    }


    public function delete_throws_runtime_when_repository_fails(): void
    {
        $this->expectException(RuntimeException::class);

        $category = $this->makeCategory();
        $childrenRelation = Mockery::mock();
        $childrenRelation->shouldReceive('exists')->once()->andReturn(false);
        $category->shouldReceive('children')->once()->andReturn($childrenRelation);

        $this->repository->shouldReceive('findById')->once()->andReturn($category);
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


    public function restore_returns_restored_category(): void
    {
        $category = $this->makeCategory();
        $this->repository->shouldReceive('restore')->once()->with(1)->andReturn($category);

        $this->assertSame($category, $this->service->restore(1));
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
        $category = $this->makeCategory(['is_active' => false]);
        $activated = $this->makeCategory(['is_active' => true]);

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($category);
        $this->repository->shouldReceive('activate')->once()->with(1)->andReturn($activated);

        $this->assertSame($activated, $this->service->activate(1));
    }


    public function activate_throws_when_already_active(): void
    {
        $this->expectException(LogicException::class);

        $category = $this->makeCategory(['is_active' => true]);
        $this->repository->shouldReceive('findById')->once()->andReturn($category);

        $this->service->activate(1);
    }


    public function activate_throws_when_category_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->shouldReceive('findById')->once()->andReturn(null);
        $this->service->activate(1);
    }


    public function deactivate_changes_status_to_inactive(): void
    {
        $category = $this->makeCategory(['is_active' => true]);
        $deactivated = $this->makeCategory(['is_active' => false]);

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($category);
        $this->repository->shouldReceive('deactivate')->once()->with(1)->andReturn($deactivated);

        $this->assertSame($deactivated, $this->service->deactivate(1));
    }


    public function deactivate_throws_when_already_inactive(): void
    {
        $this->expectException(LogicException::class);

        $category = $this->makeCategory(['is_active' => false]);
        $this->repository->shouldReceive('findById')->once()->andReturn($category);

        $this->service->deactivate(1);
    }
}
