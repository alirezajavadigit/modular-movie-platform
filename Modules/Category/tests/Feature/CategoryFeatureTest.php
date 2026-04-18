<?php

namespace Modules\Category\Tests\Feature;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Modules\Category\Contracts\CategoryServiceInterface;
use Modules\Category\Models\Category;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CategoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    private CategoryServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->service = Mockery::mock(CategoryServiceInterface::class);
        $this->app->instance(CategoryServiceInterface::class, $this->service);
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
                'id'         => 1,
                'parent_id'  => null,
                'is_active'  => true,
                'order'      => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ], $attributes) as $key => $value
        ) {
            $category->$key = $value;
        }

        $category->shouldReceive('getTranslations')->with('name')->andReturn(['en' => 'Test Category']);
        $category->shouldReceive('getTranslations')->with('slug')->andReturn(['en' => 'test-category']);
        $category->shouldReceive('getTranslations')->with('description')->andReturn([]);

        return $category;
    }

    private function makePaginator(array $items = []): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, count($items), 15, 1, ['path' => 'http://localhost']);
    }

    private function asAdmin(): static
    {
        $user = User::factory()->create();

        $permissions = [
            'categories.viewAny',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'categories.restore',
            'categories.forceDelete',
            'categories.activate',
            'categories.deactivate',
            'categories.viewTrashed',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'api');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo($permissions);

        return $this->actingAs($user, 'api');
    }

    private function storePayload(array $override = []): array
    {
        return array_merge([
            'name' => ['en' => 'Tech'],
            'slug' => ['en' => 'tech'],
        ], $override);
    }

    public function test_active_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->makeCategory()]);

        $this->service->shouldReceive('getActive')->once()->with(15)->andReturn($paginator);

        $this->getJson('/api/v1/categories/active')
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJsonPath('success', true);
    }

    public function test_tree_returns_collection(): void
    {
        $collection = new Collection([$this->makeCategory()]);

        $this->service->shouldReceive('getTree')->once()->andReturn($collection);

        $this->getJson('/api/v1/categories/tree')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_find_by_slug_returns_category(): void
    {
        $this->service->shouldReceive('findBySlug')->once()->with('test-category')->andReturn($this->makeCategory());

        $this->getJson('/api/v1/categories/slug/test-category')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_search_returns_paginated_results(): void
    {
        $this->service->shouldReceive('search')->once()->with('tech', 15)->andReturn($this->makePaginator());

        $this->getJson('/api/v1/categories/search?q=tech')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/categories')->assertUnauthorized();
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/admin/categories', $this->storePayload())->assertUnauthorized();
    }

    public function test_index_returns_paginated_categories(): void
    {
        $this->service->shouldReceive('paginate')->once()->with(15)->andReturn($this->makePaginator());

        $this->asAdmin()
            ->getJson('/api/v1/admin/categories')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_store_creates_category_and_returns_201(): void
    {
        $this->service->shouldReceive('store')->once()->andReturn($this->makeCategory());

        $this->asAdmin()
            ->postJson('/api/v1/admin/categories', $this->storePayload())
            ->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_store_fails_validation_when_name_missing(): void
    {
        $this->asAdmin()
            ->postJson('/api/v1/admin/categories', $this->storePayload(['name' => null]))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_store_fails_validation_when_slug_invalid_format(): void
    {
        $this->asAdmin()
            ->postJson('/api/v1/admin/categories', $this->storePayload(['slug' => ['en' => 'Invalid Slug!']]))
            ->assertUnprocessable();
    }

    public function test_show_returns_category(): void
    {
        $this->service->shouldReceive('findById')->once()->with(1)->andReturn($this->makeCategory());

        $this->asAdmin()
            ->getJson('/api/v1/admin/categories/1')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_update_modifies_category(): void
    {
        $this->service->shouldReceive('update')->once()->with(1, Mockery::any())->andReturn($this->makeCategory());

        $this->asAdmin()
            ->putJson('/api/v1/admin/categories/1', ['name' => ['en' => 'Updated']])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_destroy_deletes_category(): void
    {
        $this->service->shouldReceive('delete')->once()->with(1)->andReturn(true);

        $this->asAdmin()
            ->deleteJson('/api/v1/admin/categories/1')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_restore_returns_restored_category(): void
    {
        $this->service->shouldReceive('restore')->once()->with(1)->andReturn($this->makeCategory());

        $this->asAdmin()
            ->patchJson('/api/v1/admin/categories/1/restore')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_polymorphic_attach_article_to_category(): void
    {
        Mockery::close();
        $this->app->forgetInstance(CategoryServiceInterface::class);

        $category = Category::factory()->create();
        $article  = Article::factory()->create();

        $category->articles()->attach($article->id);

        $expectedType = \Illuminate\Database\Eloquent\Relations\Relation::getMorphAlias(Article::class);

        $this->assertDatabaseHas('categorizables', [
            'category_id'        => $category->id,
            'categorizable_id'   => $article->id,
            'categorizable_type' => $expectedType,
        ]);

        $this->assertTrue($category->articles()->where('articles.id', $article->id)->exists());
        $this->assertTrue($article->categories()->where('categories.id', $category->id)->exists());
    }

    public function test_polymorphic_detach_article_from_category(): void
    {
        Mockery::close();
        $this->app->forgetInstance(CategoryServiceInterface::class);

        $category = Category::factory()->create();
        $article  = Article::factory()->create();

        $category->articles()->attach($article->id);
        $category->articles()->detach($article->id);

        $expectedType = \Illuminate\Database\Eloquent\Relations\Relation::getMorphAlias(Article::class);

        $this->assertDatabaseMissing('categorizables', [
            'category_id'        => $category->id,
            'categorizable_id'   => $article->id,
            'categorizable_type' => $expectedType,
        ]);
    }
}
