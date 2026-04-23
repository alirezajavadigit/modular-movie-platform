<?php

namespace Modules\Article\Tests\Feature;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Modules\Article\Contracts\ArticleServiceInterface;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ArticleFeatureTest extends TestCase
{
    use RefreshDatabase;

    private ArticleServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->service = Mockery::mock(ArticleServiceInterface::class);
        $this->app->instance(ArticleServiceInterface::class, $this->service);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    private function makeArticle(array $attributes = []): Article
    {
        $article = Mockery::mock(Article::class)->makePartial();

        foreach (
            array_merge([
                'id'             => 1,
                'user_id'        => 1,
                'status'         => 'draft',
                'read_time'      => null,
                'is_featured'    => false,
                'allow_comments' => true,
                'published_at'   => null,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
            ], $attributes) as $key => $value
        ) {
            $article->$key = $value;
        }

        $article->shouldReceive('getTranslations')->with('title')->andReturn(['en' => 'Test Article']);
        $article->shouldReceive('getTranslations')->with('slug')->andReturn(['en' => 'test-article']);
        $article->shouldReceive('getTranslations')->with('summary')->andReturn([]);
        $article->shouldReceive('getTranslations')->with('body')->andReturn(['en' => 'Body content here.']);

        return $article;
    }

    private function makePaginator(array $items = []): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, count($items), 15, 1, [
            'path' => 'http://localhost',
        ]);
    }

    private function asAdmin(): static
    {
        $user = User::factory()->create();

        $permissions = [
            'articles.viewAny',
            'articles.view',
            'articles.create',
            'articles.update',
            'articles.delete',
            'articles.restore',
            'articles.forceDelete',
            'articles.publish',
            'articles.archive',
            'articles.markAsDraft',
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
            'title'  => ['en' => 'Test Article Title'],
            'slug'   => ['en' => 'test-article-title'],
            'body'   => ['en' => 'This is the full body content for the article.'],
            'status' => 'draft',
        ], $override);
    }

    private function updatePayload(array $override = []): array
    {
        return array_merge([
            'title' => ['en' => 'Updated Article Title'],
        ], $override);
    }

    public function test_published_returns_paginated_list(): void
    {
        $article   = $this->makeArticle(['status' => 'published']);
        $paginator = $this->makePaginator([$article]);

        $this->service->shouldReceive('getPublished')->once()->with(15)->andReturn($paginator);

        $this->getJson('/api/v1/articles/published')
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJsonPath('success', true);
    }

    public function test_published_passes_per_page_to_service(): void
    {
        $this->service->shouldReceive('getPublished')->once()->with(5)->andReturn($this->makePaginator());

        $this->getJson('/api/v1/articles/published?per_page=5')->assertOk();
    }

    public function test_find_by_slug_returns_article(): void
    {
        $article = $this->makeArticle();

        $this->service->shouldReceive('findBySlug')->once()->with('test-article')->andReturn($article);

        $this->getJson('/api/v1/articles/slug/test-article')
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJsonPath('success', true);
    }

    public function test_related_returns_collection(): void
    {
        $article    = $this->makeArticle();
        $collection = new Collection([$article]);

        $this->service->shouldReceive('getRelated')->once()->with(1)->andReturn($collection);

        $this->getJson('/api/v1/articles/1/related')
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJsonPath('success', true);
    }

    public function test_by_author_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->makeArticle()]);

        $this->service->shouldReceive('getByAuthor')->once()->with(1, 15)->andReturn($paginator);

        $this->getJson('/api/v1/articles/author/1')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_search_returns_paginated_results(): void
    {
        $paginator = $this->makePaginator([$this->makeArticle()]);

        $this->service->shouldReceive('search')->once()->with('laravel', 15)->andReturn($paginator);

        $this->getJson('/api/v1/articles/search?q=laravel')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_search_passes_per_page_to_service(): void
    {
        $this->service->shouldReceive('search')->once()->with('php', 5)->andReturn($this->makePaginator());

        $this->getJson('/api/v1/articles/search?q=php&per_page=5')->assertOk();
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/articles')->assertUnauthorized();
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/admin/articles', $this->storePayload())->assertUnauthorized();
    }

    public function test_show_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/articles/1')->assertUnauthorized();
    }

    public function test_update_requires_authentication(): void
    {
        $this->putJson('/api/v1/admin/articles/1', $this->updatePayload())->assertUnauthorized();
    }

    public function test_destroy_requires_authentication(): void
    {
        $this->deleteJson('/api/v1/admin/articles/1')->assertUnauthorized();
    }

    public function test_publish_requires_authentication(): void
    {
        $this->patchJson('/api/v1/admin/articles/1/publish')->assertUnauthorized();
    }

    public function test_archive_requires_authentication(): void
    {
        $this->patchJson('/api/v1/admin/articles/1/archive')->assertUnauthorized();
    }

    public function test_mark_as_draft_requires_authentication(): void
    {
        $this->patchJson('/api/v1/admin/articles/1/draft')->assertUnauthorized();
    }

    public function test_restore_requires_authentication(): void
    {
        $this->patchJson('/api/v1/admin/articles/1/restore')->assertUnauthorized();
    }

    public function test_force_delete_requires_authentication(): void
    {
        $this->deleteJson('/api/v1/admin/articles/1/force-delete')->assertUnauthorized();
    }

    public function test_index_returns_paginated_articles(): void
    {
        $paginator = $this->makePaginator([$this->makeArticle()]);

        $this->service->shouldReceive('paginate')->once()->with(15)->andReturn($paginator);

        $this->asAdmin()
            ->getJson('/api/v1/admin/articles')
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJsonPath('success', true);
    }

    public function test_index_passes_per_page_to_service(): void
    {
        $this->service->shouldReceive('paginate')->once()->with(10)->andReturn($this->makePaginator());

        $this->asAdmin()->getJson('/api/v1/admin/articles?per_page=10')->assertOk();
    }

    public function test_show_returns_article(): void
    {
        $article = $this->makeArticle();

        $this->service->shouldReceive('findById')->once()->with(1)->andReturn($article);

        $this->asAdmin()
            ->getJson('/api/v1/admin/articles/1')
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJsonPath('success', true);
    }

    public function test_store_creates_article_and_returns_201(): void
    {
        $article = $this->makeArticle();

        $this->service->shouldReceive('store')->once()->andReturn($article);

        $this->asAdmin()
            ->postJson('/api/v1/admin/articles', $this->storePayload())
            ->assertCreated()
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJsonPath('success', true);
    }

    public function test_store_fails_validation_when_title_missing(): void
    {
        $this->asAdmin()
            ->postJson('/api/v1/admin/articles', $this->storePayload(['title' => null]))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_store_fails_validation_when_body_missing(): void
    {
        $this->asAdmin()
            ->postJson('/api/v1/admin/articles', $this->storePayload(['body' => null]))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_store_fails_validation_when_slug_missing(): void
    {
        $this->asAdmin()
            ->postJson('/api/v1/admin/articles', $this->storePayload(['slug' => null]))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_store_fails_validation_when_slug_has_invalid_format(): void
    {
        $this->asAdmin()
            ->postJson('/api/v1/admin/articles', $this->storePayload(['slug' => ['en' => 'Invalid Slug Here']]))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_store_fails_validation_when_title_too_short(): void
    {
        $this->asAdmin()
            ->postJson('/api/v1/admin/articles', $this->storePayload(['title' => ['en' => 'ab']]))
            ->assertUnprocessable();
    }

    public function test_store_fails_validation_when_status_is_invalid(): void
    {
        $this->asAdmin()
            ->postJson('/api/v1/admin/articles', $this->storePayload(['status' => 'pending']))
            ->assertUnprocessable();
    }

    public function test_store_fails_validation_when_body_too_short(): void
    {
        $this->asAdmin()
            ->postJson('/api/v1/admin/articles', $this->storePayload(['body' => ['en' => 'Short']]))
            ->assertUnprocessable();
    }

    public function test_update_modifies_article_and_returns_200(): void
    {
        $article = $this->makeArticle();

        $this->service->shouldReceive('update')->once()->with(1, Mockery::any())->andReturn($article);

        $this->asAdmin()
            ->putJson('/api/v1/admin/articles/1', $this->updatePayload())
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJsonPath('success', true);
    }

    public function test_update_fails_validation_when_slug_has_invalid_format(): void
    {
        $this->asAdmin()
            ->putJson('/api/v1/admin/articles/1', $this->updatePayload(['slug' => ['en' => 'Invalid Slug!']]))
            ->assertUnprocessable();
    }

    public function test_update_fails_validation_when_status_is_invalid(): void
    {
        $this->asAdmin()
            ->putJson('/api/v1/admin/articles/1', $this->updatePayload(['status' => 'unknown']))
            ->assertUnprocessable();
    }

    public function test_destroy_deletes_article_and_returns_204(): void
    {
        $this->service->shouldReceive('delete')->once()->with(1)->andReturn(true);

        $this->asAdmin()
            ->deleteJson('/api/v1/admin/articles/1')
            ->assertNoContent();
    }

    public function test_publish_returns_published_article(): void
    {
        $article = $this->makeArticle(['status' => 'published']);

        $this->service->shouldReceive('publish')->once()->with(1)->andReturn($article);

        $this->asAdmin()
            ->patchJson('/api/v1/admin/articles/1/publish')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_archive_returns_archived_article(): void
    {
        $article = $this->makeArticle(['status' => 'archived']);

        $this->service->shouldReceive('archive')->once()->with(1)->andReturn($article);

        $this->asAdmin()
            ->patchJson('/api/v1/admin/articles/1/archive')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_mark_as_draft_returns_draft_article(): void
    {
        $article = $this->makeArticle(['status' => 'draft']);

        $this->service->shouldReceive('markAsDraft')->once()->with(1)->andReturn($article);

        $this->asAdmin()
            ->patchJson('/api/v1/admin/articles/1/draft')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_restore_returns_restored_article(): void
    {
        $article = $this->makeArticle();

        $this->service->shouldReceive('restore')->once()->with(1)->andReturn($article);

        $this->asAdmin()
            ->patchJson('/api/v1/admin/articles/1/restore')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_force_delete_returns_204_with_no_data(): void
    {
        $this->service->shouldReceive('forceDelete')->once()->with(1)->andReturn(true);

        $this->asAdmin()
            ->deleteJson('/api/v1/admin/articles/1/force-delete')
            ->assertNoContent();
    }

    public function test_by_status_returns_paginated_list_for_valid_status(): void
    {
        $paginator = $this->makePaginator([$this->makeArticle()]);

        $this->service->shouldReceive('getByStatus')->once()->with('published', 15)->andReturn($paginator);

        $this->asAdmin()
            ->getJson('/api/v1/admin/articles/status/published')
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
