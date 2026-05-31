<?php

namespace Modules\User\Tests\Unit;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Mockery;
use Modules\Auth\Models\User;
use Modules\User\Contracts\UserRepositoryInterface;
use Modules\User\DTOs\CreateUserDTO;
use Modules\User\DTOs\UpdateUserDTO;
use Modules\User\Services\UserService;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    private $repository;
    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(UserRepositoryInterface::class);
        $this->service = new UserService($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createDTO(array $overrides = []): CreateUserDTO
    {
        return new CreateUserDTO(
            name: $overrides['name'] ?? 'Jane Doe',
            email: array_key_exists('email', $overrides) ? $overrides['email'] : 'jane@example.com',
            phone: array_key_exists('phone', $overrides) ? $overrides['phone'] : null,
            password: $overrides['password'] ?? 'secret-password',
            roles: $overrides['roles'] ?? [],
        );
    }

    public function test_find_by_id_rejects_non_positive_id(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->findById(0);
    }

    public function test_find_by_id_delegates_to_repository(): void
    {
        $user = Mockery::mock(User::class);
        $this->repository->shouldReceive('findById')->with(5)->once()->andReturn($user);

        $this->assertSame($user, $this->service->findById(5));
    }

    public function test_paginate_rejects_out_of_range_per_page(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->paginate(500);
    }

    public function test_store_creates_user_within_transaction(): void
    {
        $user = Mockery::mock(User::class);
        $dto = $this->createDTO();

        $this->repository->shouldReceive('findByEmail')->once()->andReturn(null);
        $this->repository->shouldReceive('create')->once()->with($dto)->andReturn($user);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        $this->assertSame($user, $this->service->store($dto));
    }

    public function test_store_rejects_empty_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->store($this->createDTO(['name' => '  ']));
    }

    public function test_store_rejects_missing_email_and_phone(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->store($this->createDTO(['email' => null, 'phone' => null]));
    }

    public function test_store_rejects_duplicate_email(): void
    {
        $this->repository->shouldReceive('findByEmail')->once()->andReturn(Mockery::mock(User::class));

        $this->expectException(LogicException::class);

        $this->service->store($this->createDTO());
    }

    public function test_update_throws_when_user_missing(): void
    {
        $this->repository->shouldReceive('exists')->with(7)->once()->andReturnFalse();

        $this->expectException(InvalidArgumentException::class);

        $this->service->update(7, new UpdateUserDTO(name: 'X'));
    }

    public function test_update_delegates_within_transaction(): void
    {
        $user = Mockery::mock(User::class);
        $dto = new UpdateUserDTO(name: 'Changed');

        $this->repository->shouldReceive('exists')->with(3)->once()->andReturnTrue();
        $this->repository->shouldReceive('update')->with(3, $dto)->once()->andReturn($user);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        $this->assertSame($user, $this->service->update(3, $dto));
    }

    public function test_delete_delegates_within_transaction(): void
    {
        $this->repository->shouldReceive('exists')->with(4)->once()->andReturnTrue();
        $this->repository->shouldReceive('delete')->with(4)->once()->andReturnTrue();

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        $this->assertTrue($this->service->delete(4));
    }
}
