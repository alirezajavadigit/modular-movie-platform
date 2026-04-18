<?php

namespace Modules\Tag\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Article\Models\Article;
use Modules\Tag\DTOs\CreateTagDTO;
use Modules\Tag\DTOs\UpdateTagDTO;
use Modules\Tag\Models\Tag;
use Modules\Tag\Repositories\TagRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TagRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TagRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new TagRepository(new Tag());
    }

    private function tagData(array $overrides = []): array
    {
        static $counter = 0;
        $counter++;

        return array_merge([
            'name'        => ['en' => "Tag {$counter}"],
            'slug'        => ['en' => "tag-{$counter}"],
            'description' => ['en' => "Description {$counter}"],
            'color'       => '#ff0000',
            'is_active'   => true,
        ], $overrides);
    }


    public function test_find_by_id_returns_tag_when_found(): void
    {
        $tag = Tag::factory()->create($this->tagData());

        $result = $this->repository->findById($tag->id);

        $this->assertInstanceOf(Tag::class, $result);
        $this->assertEquals($tag->id, $result->id);
    }


    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $this->assertNull($this->repository->findById(999));
    }


    public function test_find_by_slug_returns_tag_when_found(): void
    {
        $tag = Tag::factory()->create($this->tagData(['slug' => ['en' => 'laravel-framework']]));

        $result = $this->repository->findBySlug('laravel-framework');

        $this->assertInstanceOf(Tag::class, $result);
        $this->assertEquals($tag->id, $result->id);
    }


    public function test_find_by_slug_returns_null_when_not_found(): void
    {
        $this->assertNull($this->repository->findBySlug('non-existent'));
    }


    public function test_find_by_field_returns_matching_tags(): void
    {
        Tag::factory()->count(2)->create($this->tagData(['is_active' => true]));
        Tag::factory()->count(3)->create($this->tagData(['is_active' => false]));

        $results = $this->repository->findByField('is_active', false);

        $this->assertCount(3, $results);
    }


    public function test_get_all_returns_all_tags(): void
    {
        Tag::factory()->count(4)->create($this->tagData());

        $results = $this->repository->getAll();

        $this->assertCount(4, $results);
    }


    public function test_get_all_returns_empty_when_no_tags(): void
    {
        $this->assertTrue($this->repository->getAll()->isEmpty());
    }


    public function test_paginate_returns_paginated_tags(): void
    {
        Tag::factory()->count(20)->create($this->tagData());

        $result = $this->repository->paginate(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result->items());
        $this->assertEquals(20, $result->total());
    }


    public function test_paginate_uses_default_per_page(): void
    {
        Tag::factory()->count(20)->create($this->tagData());

        $this->assertEquals(15, $this->repository->paginate()->perPage());
    }


    public function test_get_active_returns_only_active_tags(): void
    {
        Tag::factory()->count(3)->create($this->tagData(['is_active' => true]));
        Tag::factory()->count(2)->create($this->tagData(['is_active' => false]));

        $result = $this->repository->getActive();

        $this->assertEquals(3, $result->total());
    }


    public function test_get_popular_orders_tags_by_article_count_desc(): void
    {
        $popular = Tag::factory()->create($this->tagData());
        $lessPopular = Tag::factory()->create($this->tagData());
        Tag::factory()->create($this->tagData());

        $articles = Article::factory()->count(3)->create();
        $popular->articles()->attach($articles->pluck('id'));
        $lessPopular->articles()->attach($articles->first()->id);

        $result = $this->repository->getPopular(3);

        $this->assertEquals($popular->id, $result->first()->id);
        $this->assertEquals(3, $result->first()->articles_count);
    }


    public function test_get_popular_excludes_inactive_tags(): void
    {
        Tag::factory()->create($this->tagData(['is_active' => false]));
        Tag::factory()->count(2)->create($this->tagData(['is_active' => true]));

        $result = $this->repository->getPopular();

        $this->assertCount(2, $result);
    }


    public function test_get_popular_respects_limit(): void
    {
        Tag::factory()->count(10)->create($this->tagData(['is_active' => true]));

        $result = $this->repository->getPopular(5);

        $this->assertCount(5, $result);
    }


    public function test_search_finds_active_tags_matching_query(): void
    {
        Tag::factory()->create($this->tagData([
            'name'      => ['en' => 'Laravel Tag'],
            'is_active' => true,
        ]));
        Tag::factory()->create($this->tagData([
            'name'      => ['en' => 'PHP Tag'],
            'is_active' => true,
        ]));

        $result = $this->repository->search('Laravel');

        $this->assertEquals(1, $result->total());
    }


    public function test_search_excludes_inactive_tags(): void
    {
        Tag::factory()->create($this->tagData([
            'name'      => ['en' => 'Laravel Tag'],
            'is_active' => false,
        ]));

        $this->assertEquals(0, $this->repository->search('Laravel')->total());
    }


    public function test_create_persists_tag_with_dto_data(): void
    {
        $dto = new CreateTagDTO(
            name: ['en' => 'New Tag'],
            slug: ['en' => 'new-tag'],
            description: ['en' => 'A new tag'],
            color: '#00ff00',
            isActive: true,
        );

        $tag = $this->repository->create($dto);

        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertNotNull($tag->id);
        $this->assertEquals('#00ff00', $tag->color);
        $this->assertTrue((bool) $tag->is_active);
    }


    public function test_update_modifies_tag_attributes(): void
    {
        $tag = Tag::factory()->create($this->tagData(['is_active' => true, 'color' => '#ff0000']));

        $dto = new UpdateTagDTO(
            name: ['en' => 'Updated Tag'],
            slug: null,
            description: null,
            color: '#0000ff',
            isActive: false,
        );

        $updated = $this->repository->update($tag->id, $dto);

        $this->assertFalse((bool) $updated->is_active);
        $this->assertEquals('#0000ff', $updated->color);
    }


    public function test_update_throws_exception_when_tag_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $dto = new UpdateTagDTO(
            name: ['en' => 'x'],
            slug: null,
            description: null,
            color: null,
            isActive: null,
        );

        $this->repository->update(999, $dto);
    }


    public function test_delete_soft_deletes_the_tag(): void
    {
        $tag = Tag::factory()->create($this->tagData());

        $this->assertTrue($this->repository->delete($tag->id));
        $this->assertSoftDeleted('tags', ['id' => $tag->id]);
    }


    public function test_delete_throws_exception_when_tag_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->delete(999);
    }


    public function test_force_delete_permanently_removes_tag(): void
    {
        $tag = Tag::factory()->create($this->tagData());
        $tag->delete();

        $this->assertTrue($this->repository->forceDelete($tag->id));
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }


    public function test_force_delete_throws_exception_when_tag_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->forceDelete(999);
    }


    public function test_restore_recovers_a_soft_deleted_tag(): void
    {
        $tag = Tag::factory()->create($this->tagData());
        $tag->delete();

        $restored = $this->repository->restore($tag->id);

        $this->assertNull($restored->deleted_at);
    }


    public function test_restore_throws_exception_when_tag_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->restore(999);
    }


    public function test_get_trashed_returns_only_soft_deleted_tags(): void
    {
        Tag::factory()->count(2)->create($this->tagData());

        $trashed = Tag::factory()->count(3)->create($this->tagData());
        $trashed->each->delete();

        $result = $this->repository->getTrashed();

        $this->assertEquals(3, $result->total());
    }


    public function test_exists_returns_true_when_tag_exists(): void
    {
        $tag = Tag::factory()->create($this->tagData());

        $this->assertTrue($this->repository->exists($tag->id));
    }


    public function test_exists_returns_false_when_tag_does_not_exist(): void
    {
        $this->assertFalse($this->repository->exists(999));
    }


    public function test_activate_sets_is_active_to_true(): void
    {
        $tag = Tag::factory()->create($this->tagData(['is_active' => false]));

        $result = $this->repository->activate($tag->id);

        $this->assertTrue((bool) $result->is_active);
    }


    public function test_activate_throws_exception_when_tag_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->activate(999);
    }


    public function test_deactivate_sets_is_active_to_false(): void
    {
        $tag = Tag::factory()->create($this->tagData(['is_active' => true]));

        $result = $this->repository->deactivate($tag->id);

        $this->assertFalse((bool) $result->is_active);
    }


    public function test_deactivate_throws_exception_when_tag_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->deactivate(999);
    }
}
