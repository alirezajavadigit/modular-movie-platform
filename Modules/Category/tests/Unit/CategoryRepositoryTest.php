<?php

namespace Modules\Category\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Category\DTOs\CreateCategoryDTO;
use Modules\Category\DTOs\UpdateCategoryDTO;
use Modules\Category\Models\Category;
use Modules\Category\Repositories\CategoryRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CategoryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new CategoryRepository(new Category());
    }

    private function categoryData(array $overrides = []): array
    {
        static $counter = 0;
        $counter++;

        return array_merge([
            'name'        => ['en' => "Category {$counter}"],
            'slug'        => ['en' => "category-{$counter}"],
            'description' => ['en' => "Description {$counter}"],
            'is_active'   => true,
            'order'       => 0,
        ], $overrides);
    }

    public function test_find_by_id_returns_category_when_found(): void
    {
        $category = Category::factory()->create($this->categoryData());

        $result = $this->repository->findById($category->id);

        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals($category->id, $result->id);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function test_find_by_slug_returns_category_when_found(): void
    {
        $category = Category::factory()->create($this->categoryData(['slug' => ['en' => 'tech-slug']]));

        $result = $this->repository->findBySlug('tech-slug');

        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals($category->id, $result->id);
    }

    public function test_find_by_slug_returns_null_when_not_found(): void
    {
        $result = $this->repository->findBySlug('non-existent');

        $this->assertNull($result);
    }

    public function test_find_by_field_returns_matching_categories(): void
    {
        Category::factory()->count(2)->create($this->categoryData(['is_active' => true]));
        Category::factory()->count(3)->create($this->categoryData(['is_active' => false]));

        $results = $this->repository->findByField('is_active', false);

        $this->assertCount(3, $results);
    }

    public function test_find_by_field_returns_empty_collection_when_no_match(): void
    {
        $results = $this->repository->findByField('is_active', false);

        $this->assertTrue($results->isEmpty());
    }

    public function test_get_all_returns_all_categories(): void
    {
        Category::factory()->count(3)->create($this->categoryData());

        $results = $this->repository->getAll();

        $this->assertCount(3, $results);
    }

    public function test_get_all_returns_empty_collection_when_no_categories(): void
    {
        $results = $this->repository->getAll();

        $this->assertTrue($results->isEmpty());
    }

    public function test_paginate_returns_paginated_categories(): void
    {
        Category::factory()->count(20)->create($this->categoryData());

        $result = $this->repository->paginate(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result->items());
        $this->assertEquals(20, $result->total());
    }

    public function test_paginate_uses_default_per_page(): void
    {
        Category::factory()->count(20)->create($this->categoryData());

        $result = $this->repository->paginate();

        $this->assertEquals(15, $result->perPage());
    }

    public function test_get_active_returns_only_active_categories(): void
    {
        Category::factory()->count(3)->create($this->categoryData(['is_active' => true]));
        Category::factory()->count(2)->create($this->categoryData(['is_active' => false]));

        $result = $this->repository->getActive();

        $this->assertEquals(3, $result->total());
        collect($result->items())->each(fn($c) => $this->assertTrue((bool) $c->is_active));
    }

    public function test_get_by_parent_returns_categories_with_given_parent(): void
    {
        $parent = Category::factory()->create($this->categoryData());

        Category::factory()->count(3)->create($this->categoryData(['parent_id' => $parent->id]));
        Category::factory()->count(2)->create($this->categoryData(['parent_id' => null]));

        $result = $this->repository->getByParent($parent->id);

        $this->assertEquals(3, $result->total());
    }

    public function test_get_by_parent_returns_root_categories_when_null(): void
    {
        $parent = Category::factory()->create($this->categoryData());
        Category::factory()->count(2)->create($this->categoryData(['parent_id' => $parent->id]));

        $result = $this->repository->getByParent(null);

        $this->assertEquals(1, $result->total());
    }

    public function test_get_tree_returns_only_root_categories(): void
    {
        $parent = Category::factory()->create($this->categoryData());
        Category::factory()->count(3)->create($this->categoryData(['parent_id' => $parent->id]));

        $tree = $this->repository->getTree();

        $this->assertCount(1, $tree);
        $this->assertEquals($parent->id, $tree->first()->id);
    }

    public function test_search_finds_active_categories_matching_query(): void
    {
        Category::factory()->create($this->categoryData([
            'name'      => ['en' => 'Laravel Framework'],
            'is_active' => true,
        ]));
        Category::factory()->create($this->categoryData([
            'name'      => ['en' => 'PHP Language'],
            'is_active' => true,
        ]));

        $result = $this->repository->search('Laravel');

        $this->assertEquals(1, $result->total());
    }

    public function test_search_excludes_inactive_categories(): void
    {
        Category::factory()->create($this->categoryData([
            'name'      => ['en' => 'Laravel Framework'],
            'is_active' => false,
        ]));

        $result = $this->repository->search('Laravel');

        $this->assertEquals(0, $result->total());
    }

    public function test_create_persists_category_with_dto_data(): void
    {
        $dto = new CreateCategoryDTO(
            name: ['en' => 'New Category'],
            slug: ['en' => 'new-category'],
            description: ['en' => 'A description'],
            parentId: null,
            isActive: true,
            order: 5,
        );

        $category = $this->repository->create($dto);

        $this->assertInstanceOf(Category::class, $category);
        $this->assertNotNull($category->id);
        $this->assertEquals(5, $category->order);
        $this->assertTrue((bool) $category->is_active);
    }

    public function test_update_modifies_category_attributes(): void
    {
        $category = Category::factory()->create($this->categoryData(['is_active' => true, 'order' => 0]));

        $dto = new UpdateCategoryDTO(
            name: ['en' => 'Updated Name'],
            slug: null,
            description: null,
            parentId: null,
            isActive: false,
            order: 99,
        );

        $updated = $this->repository->update($category->id, $dto);

        $this->assertFalse((bool) $updated->is_active);
        $this->assertEquals(99, $updated->order);
    }

    public function test_update_throws_exception_when_category_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $dto = new UpdateCategoryDTO(
            name: ['en' => 'x'],
            slug: null,
            description: null,
            parentId: null,
            isActive: null,
            order: null,
        );

        $this->repository->update(999, $dto);
    }

    public function test_delete_soft_deletes_the_category(): void
    {
        $category = Category::factory()->create($this->categoryData());

        $result = $this->repository->delete($category->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_delete_throws_exception_when_category_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->delete(999);
    }

    public function test_force_delete_permanently_removes_category(): void
    {
        $category = Category::factory()->create($this->categoryData());
        $category->delete();

        $result = $this->repository->forceDelete($category->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_force_delete_throws_exception_when_category_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->forceDelete(999);
    }

    public function test_restore_recovers_a_soft_deleted_category(): void
    {
        $category = Category::factory()->create($this->categoryData());
        $category->delete();

        $restored = $this->repository->restore($category->id);

        $this->assertNull($restored->deleted_at);
    }

    public function test_restore_throws_exception_when_category_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->restore(999);
    }

    public function test_get_trashed_returns_only_soft_deleted_categories(): void
    {
        Category::factory()->count(2)->create($this->categoryData());

        $trashed = Category::factory()->count(3)->create($this->categoryData());
        $trashed->each->delete();

        $result = $this->repository->getTrashed();

        $this->assertEquals(3, $result->total());
    }

    public function test_exists_returns_true_when_category_exists(): void
    {
        $category = Category::factory()->create($this->categoryData());

        $this->assertTrue($this->repository->exists($category->id));
    }

    public function test_exists_returns_false_when_category_does_not_exist(): void
    {
        $this->assertFalse($this->repository->exists(999));
    }

    public function test_activate_sets_is_active_to_true(): void
    {
        $category = Category::factory()->create($this->categoryData(['is_active' => false]));

        $result = $this->repository->activate($category->id);

        $this->assertTrue((bool) $result->is_active);
    }

    public function test_activate_throws_exception_when_category_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->activate(999);
    }

    public function test_deactivate_sets_is_active_to_false(): void
    {
        $category = Category::factory()->create($this->categoryData(['is_active' => true]));

        $result = $this->repository->deactivate($category->id);

        $this->assertFalse((bool) $result->is_active);
    }

    public function test_deactivate_throws_exception_when_category_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->deactivate(999);
    }
}
