<?php

namespace Modules\Tag\Tests\Feature;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Modules\Tag\Contracts\TagServiceInterface;
use Modules\Tag\Models\Tag;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TagFeatureTest extends TestCase
{
    use RefreshDatabase;

    private TagServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->service = Mockery::mock(TagServiceInterface::class);
        $this->app->instance(TagServiceInterface::class, $this->service);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    private function makeTag(): Tag
    {
        return Tag::factory()->make(['id' => 1]);
    }

    private function createTag(): Tag
    {
        return Tag::factory()->create();
    }

    private function makePaginator(array $items = []): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, count($items), 15, 1, ['path' => 'http://localhost']);
    }

    private function asAdmin(): static
    {
        $user = User::factory()->create();

        $permissions = [
            'tags.viewAny',
            'tags.view',
            'tags.create',
            'tags.update',
            'tags.delete',
            'tags.restore',
            'tags.forceDelete',
            'tags.activate',
            'tags.deactivate',
            'tags.viewTrashed',
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
            'name' => ['en' => 'Laravel'],
            'slug' => ['en' => 'laravel'],
        ], $override);
    }

    public function test_active_returns_paginated_list(): void
    {
        $this->service->shouldReceive('getActive')->once()->with(15)->andReturn($this->makePaginator());

        $this->getJson('/api/v1/tags/active')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_popular_returns_collection(): void
    {
        $collection = new Collection([$this->makeTag()]);

        $this->service->shouldReceive('getPopular')->once()->with(10)->andReturn($collection);

        $this->getJson('/api/v1/tags/popular')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_search_returns_paginated_results(): void
    {
        $this->service->shouldReceive('search')->once()->with('laravel', 15)->andReturn($this->makePaginator());

        $this->getJson('/api/v1/tags/search?q=laravel')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_find_by_slug_returns_tag(): void
    {
        $this->service->shouldReceive('findBySlug')->once()->with('laravel')->andReturn($this->makeTag());

        $this->getJson('/api/v1/tags/slug/laravel')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/tags')->assertUnauthorized();
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/admin/tags', $this->storePayload())->assertUnauthorized();
    }

    public function test_index_returns_paginated_tags(): void
    {
        $this->service->shouldReceive('adminFilter')->once()->andReturn($this->makePaginator());

        $this->asAdmin()
            ->getJson('/api/v1/admin/tags')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_store_creates_tag_and_returns_201(): void
    {
        $this->service->shouldReceive('store')->once()->andReturn($this->makeTag());

        $this->asAdmin()
            ->postJson('/api/v1/admin/tags', $this->storePayload())
            ->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_store_fails_validation_when_name_missing(): void
    {
        $this->asAdmin()
            ->postJson('/api/v1/admin/tags', $this->storePayload(['name' => null]))
            ->assertUnprocessable();
    }

    public function test_store_fails_validation_when_color_invalid(): void
    {
        $this->asAdmin()
            ->postJson('/api/v1/admin/tags', $this->storePayload(['color' => 'not-a-color']))
            ->assertUnprocessable();
    }

    public function test_show_returns_tag(): void
    {
        $tag = $this->createTag();
        $this->service->shouldReceive('findById')->once()->with($tag->id)->andReturn($tag);

        $this->asAdmin()
            ->getJson("/api/v1/admin/tags/{$tag->id}")
            ->assertOk()
            ->assertJsonPath('success', true);
    }
    public function test_update_modifies_tag(): void
    {
        $tag = $this->createTag();
        $this->service->shouldReceive('update')->once()->with($tag->id, Mockery::any())->andReturn($tag);

        $this->asAdmin()
            ->putJson("/api/v1/admin/tags/{$tag->id}", ['name' => ['en' => 'Updated']])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_destroy_deletes_tag(): void
    {
        $tag = $this->createTag();
        $this->service->shouldReceive('delete')->once()->with($tag->id)->andReturn(true);

        $this->asAdmin()
            ->deleteJson("/api/v1/admin/tags/{$tag->id}")
            ->assertNoContent();
    }
    public function test_polymorphic_attach_article_to_tag(): void
    {
        Mockery::close();
        $this->app->forgetInstance(TagServiceInterface::class);

        $tag     = Tag::factory()->create();
        $article = Article::factory()->create();

        $tag->articles()->attach($article->id);

        $expectedType = \Illuminate\Database\Eloquent\Relations\Relation::getMorphAlias(Article::class);

        $this->assertDatabaseHas('taggables', [
            'tag_id'        => $tag->id,
            'taggable_id'   => $article->id,
            'taggable_type' => $expectedType,
        ]);

        $this->assertTrue($tag->articles()->where('articles.id', $article->id)->exists());
        $this->assertTrue($article->tags()->where('tags.id', $tag->id)->exists());
    }

    public function test_polymorphic_multiple_tags_on_single_article(): void
    {
        Mockery::close();
        $this->app->forgetInstance(TagServiceInterface::class);

        $article = Article::factory()->create();
        $tags    = Tag::factory()->count(3)->create();

        $article->tags()->sync($tags->pluck('id'));

        $this->assertCount(3, $article->tags()->get());
        $this->assertDatabaseCount('taggables', 3);
    }
}
