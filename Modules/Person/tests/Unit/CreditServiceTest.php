<?php

namespace Modules\Person\Tests\Unit\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Mockery;
use Modules\Person\Contracts\CreditRepositoryInterface;
use Modules\Person\Contracts\PersonRepositoryInterface;
use Modules\Person\DTOs\CreateCreditDTO;
use Modules\Person\DTOs\UpdateCreditDTO;
use Modules\Person\Enums\CreditRole;
use Modules\Person\Models\Credit;
use Modules\Person\Services\CreditService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreditServiceTest extends TestCase
{
    private CreditRepositoryInterface $repository;
    private PersonRepositoryInterface $personRepository;
    private CreditService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository       = Mockery::mock(CreditRepositoryInterface::class);
        $this->personRepository = Mockery::mock(PersonRepositoryInterface::class);
        $this->service          = new CreditService($this->repository, $this->personRepository);

        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'article' => \Modules\Article\Models\Article::class,
        ], false);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    private function makeCredit(array $attrs = []): Credit
    {
        $c = Mockery::mock(Credit::class)->makePartial();
        foreach (array_merge(['id' => 1], $attrs) as $k => $v) {
            $c->$k = $v;
        }
        return $c;
    }

    private function makeCreateDTO(array $override = []): CreateCreditDTO
    {
        $d = array_merge([
            'personId'       => 1,
            'creditableType' => 'article',
            'creditableId'   => 1,
            'role'           => CreditRole::ACTOR->value,
            'characterName'  => null,
            'creditedAs'     => null,
            'department'     => null,
            'order'          => 0,
        ], $override);

        return new CreateCreditDTO(
            personId: $d['personId'],
            creditableType: $d['creditableType'],
            creditableId: $d['creditableId'],
            role: $d['role'],
            characterName: $d['characterName'],
            creditedAs: $d['creditedAs'],
            department: $d['department'],
            order: $d['order'],
        );
    }

    public function test_find_by_id_throws_on_invalid_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->findById(0);
    }

    public function test_store_throws_on_invalid_role(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->store($this->makeCreateDTO(['role' => 'super-hero']));
    }

    public function test_store_throws_when_creditable_type_not_in_morph_map(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->store($this->makeCreateDTO(['creditableType' => 'unknown-type']));
    }

    public function test_store_throws_when_person_does_not_exist(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->personRepository->shouldReceive('exists')->once()->with(1)->andReturn(false);

        $this->service->store($this->makeCreateDTO());
    }

    public function test_store_creates_credit_successfully(): void
    {
        $dto = $this->makeCreateDTO();
        $credit = $this->makeCredit();

        $this->personRepository->shouldReceive('exists')->once()->with(1)->andReturn(true);
        $this->repository->shouldReceive('create')->once()->andReturn($credit);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());
        $credit->shouldReceive('refresh')->once()->andReturnSelf();

        $this->assertSame($credit, $this->service->store($dto));
    }

    public function test_update_throws_when_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->repository->shouldReceive('findById')->once()->andReturn(null);

        $dto = new UpdateCreditDTO(null, null, null, null, null);
        $this->service->update(1, $dto);
    }

    public function test_delete_throws_when_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->repository->shouldReceive('exists')->once()->andReturn(false);

        $this->service->delete(1);
    }

    public function test_delete_succeeds(): void
    {
        $this->repository->shouldReceive('exists')->once()->with(1)->andReturn(true);
        $this->repository->shouldReceive('delete')->once()->with(1)->andReturn(true);

        $this->assertTrue($this->service->delete(1));
    }

    public function test_get_by_person_throws_on_invalid_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getByPerson(0);
    }
}
