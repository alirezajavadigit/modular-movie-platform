<?php

namespace Modules\Notification\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use Mockery;
use Modules\Notification\Contracts\NotificationRepositoryInterface;
use Modules\Notification\DTOs\CreateNotificationDTO;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Models\Notification;
use Modules\Notification\Services\NotificationService;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationRepositoryInterface $repository;
    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(NotificationRepositoryInterface::class);
        $this->service    = new NotificationService($this->repository);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_find_by_id_throws_when_id_is_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->findById(0);
    }

    public function test_store_throws_when_type_is_not_registered(): void
    {
        $dto = new CreateNotificationDTO(
            notifiableType: 'SomeClass',
            notifiableId:   1,
            type:           'unregistered.type',
            channel:        NotificationChannel::DATABASE,
            data:           [],
        );

        $this->expectException(InvalidArgumentException::class);

        $this->service->store($dto);
    }

    public function test_mark_as_read_throws_when_already_read(): void
    {
        $notification = Notification::factory()->read()->make(['id' => 1]);

        $this->repository->shouldReceive('findById')->once()->with(1)->andReturn($notification);

        $this->expectException(LogicException::class);

        $this->service->markAsRead(1);
    }

    public function test_paginate_throws_when_per_page_exceeds_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->paginate(200);
    }
}
