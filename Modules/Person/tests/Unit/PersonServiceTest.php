<?php

namespace Modules\Person\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Mockery;
use Modules\Person\Contracts\PersonRepositoryInterface;
use Modules\Person\DTOs\CreatePersonDTO;
use Modules\Person\DTOs\UpdatePersonDTO;
use Modules\Person\Models\Person;
use Modules\Person\Services\PersonService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PersonServiceTest extends TestCase
{
    private PersonRepositoryInterface $repository;
    private PersonService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(PersonRepositoryInterface::class);
        $this->service    = new PersonService($this->repository);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    private function makePerson(array $attrs = []): Person
    {
        $p = Mockery::mock(Person::class)->makePartial();
        foreach (array_merge(['id' => 1, 'is_active' => true], $attrs) as $k => $v) {
            $p->$k = $v;
        }
        return $p;
    }

    private function makeCreateDTO(array $override = []): CreatePersonDTO
    {
        $d = array_merge([
            'firstName' => ['en' => 'John'],
            'lastName'  => ['en' => 'Doe'],
            'slug'      => 'john-doe',
            'biography' => null,
            'imagePath' => null,
            'dateOfBirth' => null,
            'dateOfDeath' => null,
            'placeOfBirth' => null,
            'gender' => null,
            'knownForDepartment' => null,
            'popularity' => 0.0,
            'isActive' => true,
        ], $override);

        return new CreatePersonDTO(
            firstName: $d['firstName'],
            lastName: $d['lastName'],
            slug: $d['slug'],
            biography: $d['biography'],
            imagePath: $d['imagePath'],
            dateOfBirth: $d['dateOfBirth'],
            dateOfDeath: $d['dateOfDeath'],
            placeOfBirth: $d['placeOfBirth'],
            gender: $d['gender'],
            knownForDepartment: $d['knownForDepartment'],
            popularity: $d['popularity'],
            isActive: $d['isActive'],
        );
    }

    private function makeUpdateDTO(array $override = []): UpdatePersonDTO
    {
        $d = array_merge([
            'firstName' => null,
            'lastName' => null,
            'slug' => null,
            'biography' => null,
            'imagePath' => null,
            'dateOfBirth' => null,
            'dateOfDeath' => null,
            'placeOfBirth' => null,
            'gender' => null,
            'knownForDepartment' => null,
            'popularity' => null,
            'isActive' => null,
        ], $override);

        return new UpdatePersonDTO(
            firstName: $d['firstName'],
            lastName: $d['lastName'],
            slug: $d['slug'],
            biography: $d['biography'],
            imagePath: $d['imagePath'],
            dateOfBirth: $d['dateOfBirth'],
            dateOfDeath: $d['dateOfDeath'],
            placeOfBirth: $d['placeOfBirth'],
            gender: $d['gender'],
            knownForDepartment: $d['knownForDepartment'],
            popularity: $d['popularity'],
            isActive: $d['isActive'],
        );
    }

    public function test_find_by_id_throws_on_invalid_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->findById(0);
    }

    public function test_find_by_slug_throws_on_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->findBySlug('  ');
    }

    public function test_paginate_throws_on_invalid_per_page(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->paginate(0);
    }

    public function test_get_popular_throws_on_invalid_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getPopular(0);
    }

    public function test_search_throws_on_short_query(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->search('a');
    }

    public function test_store_creates_person_successfully(): void
    {
        $dto = $this->makeCreateDTO();
        $person = $this->makePerson();

        $this->repository->shouldReceive('findBySlug')->once()->with('john-doe')->andReturn(null);
        $this->repository->shouldReceive('create')->once()->andReturn($person);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());
        $person->shouldReceive('refresh')->once()->andReturnSelf();

        $this->assertSame($person, $this->service->store($dto));
    }

    public function test_store_throws_when_slug_exists(): void
    {
        $this->expectException(LogicException::class);

        $this->repository->shouldReceive('findBySlug')->once()->andReturn($this->makePerson());

        $this->service->store($this->makeCreateDTO());
    }

    public function test_update_throws_when_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->repository->shouldReceive('findById')->once()->andReturn(null);

        $this->service->update(1, $this->makeUpdateDTO());
    }

    public function test_update_throws_when_date_of_death_before_birth(): void
    {
        $this->expectException(LogicException::class);

        $person = $this->makePerson();
        $this->repository->shouldReceive('findById')->once()->andReturn($person);

        $this->service->update(1, $this->makeUpdateDTO([
            'dateOfBirth' => '2000-01-01',
            'dateOfDeath' => '1999-01-01',
        ]));
    }

    public function test_update_throws_when_slug_taken_by_another(): void
    {
        $this->expectException(LogicException::class);

        $person = $this->makePerson(['id' => 1]);
        $other  = $this->makePerson(['id' => 2]);

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($person);
        $this->repository->shouldReceive('findBySlug')->once()->andReturn($other);

        $this->service->update(1, $this->makeUpdateDTO(['slug' => 'taken-slug']));
    }

    public function test_delete_succeeds(): void
    {
        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($this->makePerson());
        $this->repository->shouldReceive('delete')->once()->with(1)->andReturn(true);

        $this->assertTrue($this->service->delete(1));
    }

    public function test_activate_throws_when_already_active(): void
    {
        $this->expectException(LogicException::class);

        $this->repository->shouldReceive('findById')->once()->andReturn($this->makePerson(['is_active' => true]));

        $this->service->activate(1);
    }

    public function test_deactivate_throws_when_already_inactive(): void
    {
        $this->expectException(LogicException::class);

        $this->repository->shouldReceive('findById')->once()->andReturn($this->makePerson(['is_active' => false]));

        $this->service->deactivate(1);
    }
}
