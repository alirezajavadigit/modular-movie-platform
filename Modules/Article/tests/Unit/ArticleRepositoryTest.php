<?php

namespace Modules\Article\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Article\DTOs\CreateArticleDTO;
use Modules\Article\DTOs\UpdateArticleDTO;
use Modules\Article\Models\Article;
use Modules\Article\Repositories\ArticleRepository;
use Modules\Auth\Models\User;
use Modules\Authorization\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArticleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ArticleRepository $repository;
    private User $admin;
    private User $secondUser;

    private function articleData(array $overrides = []): array
    {
        return array_merge([
            'user_id' => $this->admin->id,
            'title'   => ['en' => 'Default Title'],
            'slug'    => ['en' => 'default-slug'],
            'body'    => ['en' => 'Default body content.'],
        ], $overrides);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ArticleRepository(new Article());
        Role::create(['name' => 'super-admin', 'guard_name' => 'api']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');

        $this->secondUser = User::factory()->create();
    }

    #[Test]
    public function find_by_id_returns_article_when_found(): void
    {
        $article = Article::factory()->create($this->articleData());

        $result = $this->repository->findById($article->id);

        $this->assertInstanceOf(Article::class, $result);
        $this->assertEquals($article->id, $result->id);
    }

    #[Test]
    public function find_by_id_returns_null_when_not_found(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    #[Test]
    public function find_by_slug_returns_article_when_found(): void
    {
        $article = Article::factory()->create($this->articleData(['slug' => ['en' => 'test-slug']]));

        $result = $this->repository->findBySlug('test-slug');

        $this->assertInstanceOf(Article::class, $result);
        $this->assertEquals($article->id, $result->id);
    }

    #[Test]
    public function find_by_slug_returns_null_when_not_found(): void
    {
        $result = $this->repository->findBySlug('non-existent-slug');

        $this->assertNull($result);
    }

    #[Test]
    public function find_by_field_returns_matching_articles(): void
    {
        Article::factory()->count(2)->create($this->articleData(['status' => 'published']));
        Article::factory()->count(3)->create($this->articleData(['status' => 'draft']));

        $results = $this->repository->findByField('status', 'draft');

        $this->assertCount(3, $results);
        $results->each(fn($a) => $this->assertEquals('draft', $a->status));
    }

    #[Test]
    public function find_by_field_returns_empty_collection_when_no_match(): void
    {
        $results = $this->repository->findByField('status', 'non-existent');

        $this->assertTrue($results->isEmpty());
    }

    #[Test]
    public function get_all_returns_all_articles_ordered_by_latest(): void
    {
        $first  = Article::factory()->create($this->articleData(['created_at' => now()->subDays(2)]));
        $second = Article::factory()->create($this->articleData(['created_at' => now()->subDay()]));
        $third  = Article::factory()->create($this->articleData(['created_at' => now()]));

        $results = $this->repository->getAll();

        $this->assertCount(3, $results);
        $this->assertEquals($third->id, $results->first()->id);
        $this->assertEquals($first->id, $results->last()->id);
    }

    #[Test]
    public function get_all_returns_empty_collection_when_no_articles(): void
    {
        $results = $this->repository->getAll();

        $this->assertTrue($results->isEmpty());
    }

    #[Test]
    public function paginate_returns_paginated_articles(): void
    {
        Article::factory()->count(20)->create($this->articleData());

        $result = $this->repository->paginate(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result->items());
        $this->assertEquals(20, $result->total());
    }

    #[Test]
    public function paginate_uses_default_per_page(): void
    {
        Article::factory()->count(20)->create($this->articleData());

        $result = $this->repository->paginate();

        $this->assertEquals(15, $result->perPage());
    }

    #[Test]
    public function get_published_returns_only_published_articles(): void
    {
        Article::factory()->count(3)->create($this->articleData([
            'status'       => 'published',
            'published_at' => now(),
        ]));
        Article::factory()->count(2)->create($this->articleData(['status' => 'draft']));

        $result = $this->repository->getPublished();

        $this->assertEquals(3, $result->total());
        collect($result->items())->each(fn($a) => $this->assertEquals('published', $a->status));
    }

    #[Test]
    public function get_published_excludes_articles_without_published_at(): void
    {
        Article::factory()->create($this->articleData(['status' => 'published', 'published_at' => null]));
        Article::factory()->create($this->articleData(['status' => 'published', 'published_at' => now()]));

        $result = $this->repository->getPublished();

        $this->assertEquals(1, $result->total());
    }

    #[Test]
    public function get_published_orders_by_published_at_descending(): void
    {
        $older = Article::factory()->create($this->articleData([
            'status'       => 'published',
            'published_at' => now()->subDays(5),
        ]));
        $newer = Article::factory()->create($this->articleData([
            'status'       => 'published',
            'published_at' => now(),
        ]));

        $result = $this->repository->getPublished();

        $this->assertEquals($newer->id, $result->items()[0]->id);
        $this->assertEquals($older->id, $result->items()[1]->id);
    }

    #[Test]
    public function get_drafts_returns_only_draft_articles(): void
    {
        Article::factory()->count(3)->create($this->articleData(['status' => 'draft']));
        Article::factory()->count(2)->create($this->articleData(['status' => 'published', 'published_at' => now()]));

        $result = $this->repository->getDrafts();

        $this->assertEquals(3, $result->total());
        collect($result->items())->each(fn($a) => $this->assertEquals('draft', $a->status));
    }

    #[Test]
    public function get_archived_returns_only_archived_articles(): void
    {
        Article::factory()->count(2)->create($this->articleData(['status' => 'archived']));
        Article::factory()->count(3)->create($this->articleData(['status' => 'draft']));

        $result = $this->repository->getArchived();

        $this->assertEquals(2, $result->total());
        collect($result->items())->each(fn($a) => $this->assertEquals('archived', $a->status));
    }

    #[Test]
    public function get_by_status_returns_articles_with_given_status(): void
    {
        Article::factory()->count(4)->create($this->articleData(['status' => 'published', 'published_at' => now()]));
        Article::factory()->count(2)->create($this->articleData(['status' => 'draft']));

        $result = $this->repository->getByStatus('published');

        $this->assertEquals(4, $result->total());
    }

    #[Test]
    public function get_by_status_returns_empty_paginator_for_unknown_status(): void
    {
        Article::factory()->count(3)->create($this->articleData(['status' => 'draft']));

        $result = $this->repository->getByStatus('scheduled');

        $this->assertEquals(0, $result->total());
    }

    #[Test]
    public function get_by_author_returns_articles_for_given_user(): void
    {
        Article::factory()->count(3)->create($this->articleData(['user_id' => $this->admin->id]));
        Article::factory()->count(2)->create($this->articleData(['user_id' => $this->secondUser->id]));

        $result = $this->repository->getByAuthor($this->admin->id);

        $this->assertEquals(3, $result->total());
        collect($result->items())->each(fn($a) => $this->assertEquals($this->admin->id, $a->user_id));
    }

    #[Test]
    public function get_by_author_returns_empty_paginator_when_no_articles(): void
    {
        $result = $this->repository->getByAuthor(999);

        $this->assertEquals(0, $result->total());
    }

    #[Test]
    public function get_related_throws_exception_when_article_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->getRelated(999);
    }

    #[Test]
    public function search_finds_articles_by_title(): void
    {
        Article::factory()->create($this->articleData([
            'title'        => ['en' => 'Laravel Testing Guide'],
            'status'       => 'published',
            'published_at' => now(),
        ]));
        Article::factory()->create($this->articleData([
            'title'        => ['en' => 'Vue.js Basics'],
            'status'       => 'published',
            'published_at' => now(),
        ]));

        $result = $this->repository->search('Laravel');

        $this->assertEquals(1, $result->total());
    }

    #[Test]
    public function search_finds_articles_by_body(): void
    {
        Article::factory()->create($this->articleData([
            'body'         => ['en' => 'This article covers PHPUnit testing.'],
            'status'       => 'published',
            'published_at' => now(),
        ]));

        $result = $this->repository->search('PHPUnit');

        $this->assertEquals(1, $result->total());
    }

    #[Test]
    public function search_finds_articles_by_summary(): void
    {
        Article::factory()->create($this->articleData([
            'summary'      => ['en' => 'An introduction to Eloquent ORM.'],
            'status'       => 'published',
            'published_at' => now(),
        ]));

        $result = $this->repository->search('Eloquent');

        $this->assertEquals(1, $result->total());
    }

    #[Test]
    public function search_only_returns_published_articles(): void
    {
        Article::factory()->create($this->articleData([
            'title'  => ['en' => 'Draft Article about Laravel'],
            'status' => 'draft',
        ]));

        $result = $this->repository->search('Laravel');

        $this->assertEquals(0, $result->total());
    }

    #[Test]
    public function search_returns_empty_when_no_match(): void
    {
        Article::factory()->create($this->articleData(['status' => 'published', 'published_at' => now()]));

        $result = $this->repository->search('xyznonexistent');

        $this->assertEquals(0, $result->total());
    }

    #[Test]
    public function create_persists_article_with_correct_attributes(): void
    {
        $dto = new CreateArticleDTO(
            userId: $this->admin->id,
            title: ['en' => 'Test Article'],
            slug: ['en' => 'test-article'],
            summary: ['en' => 'A brief summary.'],
            body: ['en' => 'Full body content.'],
            status: 'draft',
            readTime: 5,
            isFeatured: false,
            allowComments: true,
            publishedAt: null,
        );

        $article = $this->repository->create($dto);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertNotNull($article->id);
        $this->assertEquals($this->admin->id, $article->user_id);
        $this->assertEquals('draft', $article->status);
        $this->assertEquals(5, $article->read_time);
        $this->assertFalse((bool) $article->is_featured);
        $this->assertTrue((bool) $article->allow_comments);
        $this->assertNull($article->published_at);
    }

    #[Test]
    public function create_returns_an_article_model(): void
    {
        $dto = new CreateArticleDTO(
            userId: $this->admin->id,
            title: ['en' => 'Another Article'],
            slug: ['en' => 'another-article'],
            summary: null,
            body: ['en' => 'Body content.'],
            status: 'draft',
            readTime: null,
            isFeatured: false,
            allowComments: false,
            publishedAt: null,
        );

        $article = $this->repository->create($dto);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertNotNull($article->id);
    }

    #[Test]
    public function update_modifies_article_attributes(): void
    {
        $article = Article::factory()->create($this->articleData(['title' => ['en' => 'Old Title']]));

        $dto = new UpdateArticleDTO(
            title: ['en' => 'Updated Title'],
            slug: ['en' => 'updated-slug'],
            summary: ['en' => 'Updated summary.'],
            body: ['en' => 'Updated body.'],
            status: 'published',
            readTime: 10,
            isFeatured: true,
            allowComments: false,
            publishedAt: now(),
        );

        $updated = $this->repository->update($article->id, $dto);

        $this->assertEquals('published', $updated->status);
        $this->assertEquals(10, $updated->read_time);
        $this->assertTrue((bool) $updated->is_featured);
        $this->assertNotNull($updated->published_at);
    }

    #[Test]
    public function update_throws_exception_when_article_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $dto = new UpdateArticleDTO(
            title: ['en' => 'Title'],
            slug: ['en' => 'slug'],
            summary: null,
            body: ['en' => 'Body'],
            status: 'draft',
            readTime: null,
            isFeatured: false,
            allowComments: true,
            publishedAt: null,
        );

        $this->repository->update(999, $dto);
    }

    #[Test]
    public function delete_soft_deletes_the_article(): void
    {
        $article = Article::factory()->create($this->articleData());

        $result = $this->repository->delete($article->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('articles', ['id' => $article->id]);
    }

    #[Test]
    public function delete_throws_exception_when_article_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->delete(999);
    }

    #[Test]
    public function force_delete_permanently_removes_article(): void
    {
        $article = Article::factory()->create($this->articleData());
        $article->delete();

        $result = $this->repository->forceDelete($article->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }

    #[Test]
    public function force_delete_throws_exception_when_article_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->forceDelete(999);
    }

    #[Test]
    public function restore_recovers_a_soft_deleted_article(): void
    {
        $article = Article::factory()->create($this->articleData());
        $article->delete();

        $restored = $this->repository->restore($article->id);

        $this->assertNull($restored->deleted_at);
    }

    #[Test]
    public function restore_throws_exception_when_article_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->restore(999);
    }

    #[Test]
    public function get_trashed_returns_only_soft_deleted_articles(): void
    {
        Article::factory()->count(2)->create($this->articleData());

        $trashed = Article::factory()->count(3)->create($this->articleData());
        $trashed->each->delete();

        $result = $this->repository->getTrashed();

        $this->assertEquals(3, $result->total());
    }

    #[Test]
    public function exists_returns_true_when_article_exists(): void
    {
        $article = Article::factory()->create($this->articleData());

        $this->assertTrue($this->repository->exists($article->id));
    }

    #[Test]
    public function exists_returns_false_when_article_does_not_exist(): void
    {
        $this->assertFalse($this->repository->exists(999));
    }

    #[Test]
    public function publish_sets_status_to_published_and_sets_published_at(): void
    {
        $article = Article::factory()->create($this->articleData(['status' => 'draft', 'published_at' => null]));

        $result = $this->repository->publish($article->id);

        $this->assertEquals('published', $result->status);
        $this->assertNotNull($result->published_at);
    }

    #[Test]
    public function publish_throws_exception_when_article_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->publish(999);
    }

    #[Test]
    public function archive_sets_status_to_archived(): void
    {
        $article = Article::factory()->create($this->articleData(['status' => 'published', 'published_at' => now()]));

        $result = $this->repository->archive($article->id);

        $this->assertEquals('archived', $result->status);
    }

    #[Test]
    public function archive_throws_exception_when_article_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->archive(999);
    }

    #[Test]
    public function mark_as_draft_sets_status_to_draft_and_clears_published_at(): void
    {
        $article = Article::factory()->create($this->articleData([
            'status'       => 'published',
            'published_at' => now(),
        ]));

        $result = $this->repository->markAsDraft($article->id);

        $this->assertEquals('draft', $result->status);
        $this->assertNull($result->published_at);
    }

    #[Test]
    public function mark_as_draft_throws_exception_when_article_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->markAsDraft(999);
    }

    #[Test]
    public function sync_categories_throws_exception_when_article_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->syncCategories(999, [1, 2]);
    }

    #[Test]
    public function sync_tags_throws_exception_when_article_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->syncTags(999, [1, 2]);
    }
}
