<?php

namespace Modules\Article\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Mockery;
use Modules\Article\Contracts\ArticleRepositoryInterface;
use Modules\Article\DTOs\CreateArticleDTO;
use Modules\Article\DTOs\UpdateArticleDTO;
use Modules\Article\Models\Article;
use Modules\Article\Services\ArticleService;
use RuntimeException;
use Tests\TestCase;

class ArticleServiceTest extends TestCase
{
    private ArticleRepositoryInterface $repository;
    private ArticleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(ArticleRepositoryInterface::class);
        $this->service    = new ArticleService($this->repository);
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
                'id'     => 1,
                'title'  => 'Test Article',
                'body'   => 'Test body content',
                'slug'   => ['en' => 'test-article'],
                'status' => 'draft',
            ], $attributes) as $key => $value
        ) {
            $article->$key = $value;
        }

        return $article;
    }

    private function makePaginator(array $items = []): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, count($items), 15);
    }

    private function makeCreateDTO(array $override = []): CreateArticleDTO
    {
        $data = array_merge([
            'userId'        => 1,
            'title'         => ['en' => 'Test Article'],
            'slug'          => ['en' => 'test-article'],
            'summary'       => null,
            'body'          => ['en' => 'Some body content.'],
            'status'        => 'draft',
            'readTime'      => null,
            'isFeatured'    => false,
            'allowComments' => true,
            'publishedAt'   => null,
        ], $override);

        return new CreateArticleDTO(
            userId: $data['userId'],
            title: $data['title'],
            slug: $data['slug'],
            summary: $data['summary'],
            body: $data['body'],
            status: $data['status'],
            readTime: $data['readTime'],
            isFeatured: $data['isFeatured'],
            allowComments: $data['allowComments'],
            publishedAt: $data['publishedAt'],
        );
    }

    private function makeUpdateDTO(array $override = []): UpdateArticleDTO
    {
        $data = array_merge([
            'title'         => ['en' => 'Updated Article'],
            'slug'          => null,
            'summary'       => null,
            'body'          => null,
            'status'        => null,
            'readTime'      => null,
            'isFeatured'    => null,
            'allowComments' => null,
            'publishedAt'   => null,
        ], $override);

        return new UpdateArticleDTO(
            title: $data['title'],
            slug: $data['slug'],
            summary: $data['summary'],
            body: $data['body'],
            status: $data['status'],
            readTime: $data['readTime'],
            isFeatured: $data['isFeatured'],
            allowComments: $data['allowComments'],
            publishedAt: $data['publishedAt'],
        );
    }

    public function test_findById_returns_article_when_found(): void
    {
        $article = $this->makeArticle();

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($article);

        $this->assertSame($article, $this->service->findById(1));
    }

    public function test_findById_returns_null_when_not_found(): void
    {
        $this->repository->shouldReceive('findById')->once()->andReturn(null);

        $this->assertNull($this->service->findById(99));
    }

    public function test_findById_throws_when_id_is_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Article ID must be a positive integer.');

        $this->service->findById(0);
    }

    public function test_findById_throws_when_id_is_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->findById(-5);
    }

    public function test_findBySlug_returns_article_when_found(): void
    {
        $article = $this->makeArticle();

        $this->repository->shouldReceive('findBySlug')->once()->with('test-article')->andReturn($article);

        $this->assertSame($article, $this->service->findBySlug('test-article'));
    }

    public function test_findBySlug_returns_null_when_not_found(): void
    {
        $this->repository->shouldReceive('findBySlug')->once()->andReturn(null);

        $this->assertNull($this->service->findBySlug('nonexistent'));
    }

    public function test_findBySlug_throws_when_slug_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be empty.');

        $this->service->findBySlug('');
    }

    public function test_findBySlug_throws_when_slug_is_whitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->findBySlug('   ');
    }

    public function test_getAll_returns_collection(): void
    {
        $collection = new Collection([$this->makeArticle()]);

        $this->repository->shouldReceive('getAll')->once()->andReturn($collection);

        $this->assertSame($collection, $this->service->getAll());
    }

    public function test_paginate_returns_paginator(): void
    {
        $paginator = $this->makePaginator();

        $this->repository->shouldReceive('paginate')->once()->with(15)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->paginate(15));
    }

    public function test_paginate_throws_when_perPage_is_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Per page must be between 1 and 100.');

        $this->service->paginate(0);
    }

    public function test_paginate_throws_when_perPage_exceeds_maximum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->paginate(101);
    }

    public function test_paginate_accepts_boundary_values(): void
    {
        $this->repository->shouldReceive('paginate')->andReturn($this->makePaginator());

        $this->service->paginate(1);
        $this->service->paginate(100);

        $this->addToAssertionCount(2);
    }

    public function test_getPublished_delegates_to_repository(): void
    {
        $paginator = $this->makePaginator();

        $this->repository->shouldReceive('getPublished')->once()->with(15)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->getPublished(15));
    }

    public function test_getPublished_throws_when_perPage_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->getPublished(0);
    }

    public function test_getDrafts_delegates_to_repository(): void
    {
        $paginator = $this->makePaginator();

        $this->repository->shouldReceive('getDrafts')->once()->with(10)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->getDrafts(10));
    }

    public function test_getDrafts_throws_when_perPage_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->getDrafts(101);
    }

    public function test_getArchived_delegates_to_repository(): void
    {
        $paginator = $this->makePaginator();

        $this->repository->shouldReceive('getArchived')->once()->with(20)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->getArchived(20));
    }

    public function test_getArchived_throws_when_perPage_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->getArchived(0);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validStatusProvider')]
    public function test_getByStatus_returns_paginator_for_valid_status(string $status): void
    {
        $paginator = $this->makePaginator();

        $this->repository->shouldReceive('getByStatus')->once()->with($status, 15)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->getByStatus($status, 15));
    }

    public static function validStatusProvider(): array
    {
        return [
            ['draft'],
            ['published'],
            ['archived'],
        ];
    }

    public function test_getByStatus_throws_for_invalid_status(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid status 'pending'");

        $this->service->getByStatus('pending');
    }

    public function test_getByStatus_throws_when_perPage_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->getByStatus('draft', 101);
    }

    public function test_getByAuthor_returns_paginator(): void
    {
        $paginator = $this->makePaginator();

        $this->repository->shouldReceive('getByAuthor')->once()->with(5, 15)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->getByAuthor(5, 15));
    }

    public function test_getByAuthor_throws_when_userId_is_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer.');

        $this->service->getByAuthor(0);
    }

    public function test_getByAuthor_throws_when_userId_is_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->getByAuthor(-1);
    }

    public function test_getRelated_returns_collection(): void
    {
        $article    = $this->makeArticle();
        $collection = new Collection();

        $this->repository->shouldReceive('findById')->andReturn($article);
        $this->repository->shouldReceive('getRelated')->once()->with(1, 5)->andReturn($collection);

        $this->assertSame($collection, $this->service->getRelated(1, 5));
    }

    public function test_getRelated_throws_when_article_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Article with ID 99 not found.');

        $this->repository->shouldReceive('findById')->andReturn(null);

        $this->service->getRelated(99);
    }

    public function test_getRelated_throws_when_id_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->getRelated(0);
    }

    public function test_getRelated_throws_when_limit_is_out_of_range(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be between 1 and 50.');

        $this->service->getRelated(1, 51);
    }

    public function test_getRelated_throws_when_limit_is_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->getRelated(1, 0);
    }

    public function test_search_returns_paginator(): void
    {
        $paginator = $this->makePaginator();

        $this->repository->shouldReceive('search')->once()->with('laravel', 15)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->search('laravel', 15));
    }

    public function test_search_throws_when_query_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Search query cannot be empty.');

        $this->service->search('');
    }

    public function test_search_throws_when_query_is_whitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->search('  ');
    }

    public function test_search_throws_when_query_is_too_short(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Search query must be at least 2 characters.');

        $this->service->search('a');
    }

    public function test_search_throws_when_perPage_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->search('laravel', 0);
    }

    public function test_store_creates_article_successfully(): void
    {
        $dto     = $this->makeCreateDTO();
        $article = $this->makeArticle();

        $this->repository->shouldReceive('findBySlug')->andReturn(null);
        $this->repository->shouldReceive('create')->once()->andReturn($article);

        $article->shouldReceive('refresh')->once()->andReturn($article);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        $this->assertSame($article, $this->service->store($dto));
    }

    public function test_store_throws_when_title_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Article title is required.');

        $this->service->store($this->makeCreateDTO(['title' => []]));
    }

    public function test_store_throws_when_body_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Article body is required.');

        $this->service->store($this->makeCreateDTO(['body' => []]));
    }

    public function test_store_throws_when_slug_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Article slug is required.');

        $this->service->store($this->makeCreateDTO(['slug' => []]));
    }

    public function test_store_throws_when_slug_already_exists(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('An article with this slug already exists.');

        $this->repository->shouldReceive('findBySlug')->andReturn($this->makeArticle());

        $this->service->store($this->makeCreateDTO());
    }

    public function test_update_updates_article_successfully(): void
    {
        $existing = $this->makeArticle(['status' => 'draft']);
        $updated  = $this->makeArticle(['title' => 'Updated Title']);

        $this->repository->shouldReceive('findById')->andReturn($existing);
        $this->repository->shouldReceive('findBySlug')->andReturn(null);
        $this->repository->shouldReceive('update')->once()->andReturn($updated);

        $updated->shouldReceive('refresh')->andReturn($updated);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        $this->assertSame($updated, $this->service->update(1, $this->makeUpdateDTO()));
    }

    public function test_update_throws_when_id_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Article ID must be a positive integer.');

        $this->service->update(0, $this->makeUpdateDTO());
    }

    public function test_update_throws_when_article_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Article with ID 99 not found.');

        $this->repository->shouldReceive('findById')->andReturn(null);

        $this->service->update(99, $this->makeUpdateDTO());
    }

    public function test_update_throws_when_reverting_published_to_draft(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot revert a published article to draft directly. Archive it first.');

        $existing = $this->makeArticle(['status' => 'published']);

        $this->repository->shouldReceive('findById')->andReturn($existing);

        $this->service->update(1, $this->makeUpdateDTO(['status' => 'draft']));
    }

    public function test_update_throws_when_slug_belongs_to_different_article(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Another article with this slug already exists.');

        $existing    = $this->makeArticle(['id' => 1, 'status' => 'draft']);
        $conflicting = $this->makeArticle(['id' => 2]);

        $this->repository->shouldReceive('findById')->andReturn($existing);
        $this->repository->shouldReceive('findBySlug')->andReturn($conflicting);

        $this->service->update(1, $this->makeUpdateDTO(['slug' => ['en' => 'taken-slug']]));
    }

    public function test_update_does_not_throw_when_slug_belongs_to_same_article(): void
    {
        $existing = $this->makeArticle(['id' => 1, 'status' => 'draft']);

        $this->repository->shouldReceive('findById')->andReturn($existing);
        $this->repository->shouldReceive('findBySlug')->andReturn($existing);
        $this->repository->shouldReceive('update')->andReturn($existing);

        $existing->shouldReceive('refresh')->andReturn($existing);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        $this->assertSame($existing, $this->service->update(1, $this->makeUpdateDTO(['slug' => ['en' => 'same-slug']])));
    }

    public function test_delete_returns_true_on_success(): void
    {
        $article = $this->makeArticle(['status' => 'draft']);

        $this->repository->shouldReceive('findById')->andReturn($article);
        $this->repository->shouldReceive('delete')->once()->with(1)->andReturn(true);

        $this->assertTrue($this->service->delete(1));
    }

    public function test_delete_throws_when_id_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->delete(0);
    }

    public function test_delete_throws_when_article_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Article with ID 1 not found.');

        $this->repository->shouldReceive('findById')->andReturn(null);

        $this->service->delete(1);
    }

    public function test_delete_throws_when_article_is_published(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot delete a published article. Archive it first.');

        $article = $this->makeArticle(['status' => 'published']);

        $this->repository->shouldReceive('findById')->andReturn($article);

        $this->service->delete(1);
    }

    public function test_delete_throws_when_repository_returns_false(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to delete article with ID 1.');

        $article = $this->makeArticle(['status' => 'draft']);

        $this->repository->shouldReceive('findById')->andReturn($article);
        $this->repository->shouldReceive('delete')->andReturn(false);

        $this->service->delete(1);
    }

    public function test_forceDelete_returns_true_on_success(): void
    {
        $article = $this->makeArticle();

        $this->repository->shouldReceive('findById')->andReturn($article);
        $this->repository->shouldReceive('forceDelete')->once()->with(1)->andReturn(true);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        $this->assertTrue($this->service->forceDelete(1));
    }

    public function test_forceDelete_throws_when_id_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->forceDelete(0);
    }

    public function test_forceDelete_throws_when_article_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Article with ID 1 not found.');

        $this->repository->shouldReceive('findById')->andReturn(null);

        $this->service->forceDelete(1);
    }

    public function test_forceDelete_throws_when_repository_returns_false(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to permanently delete article with ID 1.');

        $article = $this->makeArticle();

        $this->repository->shouldReceive('findById')->andReturn($article);
        $this->repository->shouldReceive('forceDelete')->andReturn(false);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        $this->service->forceDelete(1);
    }

    public function test_restore_returns_article_on_success(): void
    {
        $article = $this->makeArticle();

        $this->repository->shouldReceive('restore')->once()->with(1)->andReturn($article);

        $this->assertSame($article, $this->service->restore(1));
    }

    public function test_restore_throws_when_id_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->restore(0);
    }

    public function test_getTrashed_returns_paginator(): void
    {
        $paginator = $this->makePaginator();

        $this->repository->shouldReceive('getTrashed')->once()->with(15)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->getTrashed(15));
    }

    public function test_getTrashed_throws_when_perPage_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->getTrashed(0);
    }

    public function test_publish_returns_published_article(): void
    {
        $article   = $this->makeArticle(['status' => 'draft']);
        $published = $this->makeArticle(['status' => 'published']);

        $this->repository->shouldReceive('findById')->andReturn($article);
        $this->repository->shouldReceive('publish')->once()->with(1)->andReturn($published);

        $this->assertSame($published, $this->service->publish(1));
    }

    public function test_publish_throws_when_id_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->publish(0);
    }

    public function test_publish_throws_when_article_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Article with ID 1 not found.');

        $this->repository->shouldReceive('findById')->andReturn(null);

        $this->service->publish(1);
    }

    public function test_publish_throws_when_already_published(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Article is already published.');

        $article = $this->makeArticle(['status' => 'published']);

        $this->repository->shouldReceive('findById')->andReturn($article);

        $this->service->publish(1);
    }

    public function test_publish_throws_when_body_is_empty(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot publish an article without body content.');

        $article = $this->makeArticle(['status' => 'draft', 'body' => '']);

        $this->repository->shouldReceive('findById')->andReturn($article);

        $this->service->publish(1);
    }

    public function test_publish_throws_when_title_is_empty(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot publish an article without a title.');

        $article = $this->makeArticle(['status' => 'draft', 'title' => '']);

        $this->repository->shouldReceive('findById')->andReturn($article);

        $this->service->publish(1);
    }

    public function test_archive_returns_archived_article(): void
    {
        $article  = $this->makeArticle(['status' => 'published']);
        $archived = $this->makeArticle(['status' => 'archived']);

        $this->repository->shouldReceive('findById')->andReturn($article);
        $this->repository->shouldReceive('archive')->once()->with(1)->andReturn($archived);

        $this->assertSame($archived, $this->service->archive(1));
    }

    public function test_archive_throws_when_id_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->archive(0);
    }

    public function test_archive_throws_when_article_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Article with ID 1 not found.');

        $this->repository->shouldReceive('findById')->andReturn(null);

        $this->service->archive(1);
    }

    public function test_archive_throws_when_already_archived(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Article is already archived.');

        $article = $this->makeArticle(['status' => 'archived']);

        $this->repository->shouldReceive('findById')->andReturn($article);

        $this->service->archive(1);
    }

    public function test_archive_throws_when_article_is_draft(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot archive a draft article. Publish it first.');

        $article = $this->makeArticle(['status' => 'draft']);

        $this->repository->shouldReceive('findById')->andReturn($article);

        $this->service->archive(1);
    }

    public function test_markAsDraft_returns_draft_article(): void
    {
        $article = $this->makeArticle(['status' => 'archived']);
        $draft   = $this->makeArticle(['status' => 'draft']);

        $this->repository->shouldReceive('findById')->andReturn($article);
        $this->repository->shouldReceive('markAsDraft')->once()->with(1)->andReturn($draft);

        $this->assertSame($draft, $this->service->markAsDraft(1));
    }

    public function test_markAsDraft_throws_when_id_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->markAsDraft(0);
    }

    public function test_markAsDraft_throws_when_article_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Article with ID 1 not found.');

        $this->repository->shouldReceive('findById')->andReturn(null);

        $this->service->markAsDraft(1);
    }

    public function test_markAsDraft_throws_when_already_draft(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Article is already a draft.');

        $article = $this->makeArticle(['status' => 'draft']);

        $this->repository->shouldReceive('findById')->andReturn($article);

        $this->service->markAsDraft(1);
    }

    public function test_markAsDraft_throws_when_article_is_published(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot revert a published article to draft. Archive it first.');

        $article = $this->makeArticle(['status' => 'published']);

        $this->repository->shouldReceive('findById')->andReturn($article);

        $this->service->markAsDraft(1);
    }
}
