<?php

namespace Modules\Person\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Person\DTOs\CreatePersonDTO;
use Modules\Person\DTOs\UpdatePersonDTO;
use Modules\Person\Models\Person;
use Modules\Person\Repositories\PersonRepository;
use Modules\Person\Tests\Concerns\LoadsMediaLibrary;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PersonRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use LoadsMediaLibrary;

    private PersonRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMediaLibraryMigration();
        $this->repository = new PersonRepository(new Person());
    }

    public function test_find_by_id_returns_person_when_found(): void
    {
        $person = Person::factory()->create();

        $this->assertEquals($person->id, $this->repository->findById($person->id)->id);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $this->assertNull($this->repository->findById(999));
    }

    public function test_find_by_slug_returns_person_when_found(): void
    {
        $person = Person::factory()->create(['slug' => 'john-doe']);

        $this->assertEquals($person->id, $this->repository->findBySlug('john-doe')->id);
    }

    public function test_find_by_slug_returns_null_when_not_found(): void
    {
        $this->assertNull($this->repository->findBySlug('non-existent'));
    }

    public function test_paginate_returns_paginator(): void
    {
        Person::factory()->count(20)->create();

        $result = $this->repository->paginate(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result->items());
        $this->assertEquals(20, $result->total());
    }

    public function test_get_active_returns_only_active(): void
    {
        Person::factory()->count(3)->create(['is_active' => true]);
        Person::factory()->count(2)->create(['is_active' => false]);

        $this->assertEquals(3, $this->repository->getActive()->total());
    }

    public function test_get_popular_orders_by_popularity_desc(): void
    {
        $low  = Person::factory()->create(['popularity' => 10]);
        $high = Person::factory()->create(['popularity' => 99]);

        $result = $this->repository->getPopular(2);

        $this->assertEquals($high->id, $result->first()->id);
    }

    public function test_get_by_department_filters_correctly(): void
    {
        Person::factory()->count(3)->create(['known_for_department' => 'Acting', 'is_active' => true]);
        Person::factory()->count(2)->create(['known_for_department' => 'Directing', 'is_active' => true]);

        $this->assertEquals(3, $this->repository->getByDepartment('Acting')->total());
    }

    public function test_search_finds_matching_persons(): void
    {
        Person::factory()->create(['slug' => 'nolan-christopher', 'is_active' => true]);
        Person::factory()->create(['slug' => 'tarantino-quentin', 'is_active' => true]);

        $this->assertEquals(1, $this->repository->search('nolan')->total());
    }

    public function test_search_excludes_inactive(): void
    {
        Person::factory()->create(['slug' => 'nolan-christopher', 'is_active' => false]);

        $this->assertEquals(0, $this->repository->search('nolan')->total());
    }

    public function test_create_persists_person(): void
    {
        $dto = new CreatePersonDTO(
            firstName: ['en' => 'Christopher'],
            lastName: ['en' => 'Nolan'],
            slug: 'christopher-nolan',
            biography: ['en' => 'Director'],
            dateOfBirth: '1970-07-30',
            dateOfDeath: null,
            placeOfBirth: ['en' => 'London'],
            gender: 'male',
            knownForDepartment: 'Directing',
            popularity: 95.5,
            isActive: true,
        );

        $person = $this->repository->create($dto);

        $this->assertNotNull($person->id);
        $this->assertEquals('christopher-nolan', $person->slug);
    }

    public function test_update_modifies_attributes(): void
    {
        $person = Person::factory()->create(['popularity' => 10, 'is_active' => true]);

        $dto = new UpdatePersonDTO(
            firstName: null,
            lastName: null,
            slug: null,
            biography: null,
            dateOfBirth: null,
            dateOfDeath: null,
            placeOfBirth: null,
            gender: null,
            knownForDepartment: null,
            popularity: 99.9,
            isActive: false,
        );

        $updated = $this->repository->update($person->id, $dto);

        $this->assertEquals(99.9, (float) $updated->popularity);
        $this->assertFalse((bool) $updated->is_active);
    }

    public function test_update_throws_when_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $dto = new UpdatePersonDTO(null, null, null, null, null, null, null, null, null, null, null, null);
        $this->repository->update(999, $dto);
    }

    public function test_delete_soft_deletes(): void
    {
        $person = Person::factory()->create();

        $this->assertTrue($this->repository->delete($person->id));
        $this->assertSoftDeleted('persons', ['id' => $person->id]);
    }

    public function test_force_delete_permanently_removes(): void
    {
        $person = Person::factory()->create();
        $person->delete();

        $this->assertTrue($this->repository->forceDelete($person->id));
        $this->assertDatabaseMissing('persons', ['id' => $person->id]);
    }

    public function test_restore_recovers_soft_deleted(): void
    {
        $person = Person::factory()->create();
        $person->delete();

        $this->assertNull($this->repository->restore($person->id)->deleted_at);
    }

    public function test_get_trashed_returns_only_soft_deleted(): void
    {
        Person::factory()->count(2)->create();
        $trashed = Person::factory()->count(3)->create();
        $trashed->each->delete();

        $this->assertEquals(3, $this->repository->getTrashed()->total());
    }

    public function test_exists_returns_correct_boolean(): void
    {
        $person = Person::factory()->create();

        $this->assertTrue($this->repository->exists($person->id));
        $this->assertFalse($this->repository->exists(999));
    }

    public function test_activate_sets_is_active_true(): void
    {
        $person = Person::factory()->create(['is_active' => false]);

        $this->assertTrue((bool) $this->repository->activate($person->id)->is_active);
    }

    public function test_deactivate_sets_is_active_false(): void
    {
        $person = Person::factory()->create(['is_active' => true]);

        $this->assertFalse((bool) $this->repository->deactivate($person->id)->is_active);
    }
}
