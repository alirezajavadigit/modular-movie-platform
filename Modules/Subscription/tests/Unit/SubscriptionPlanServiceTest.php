<?php

namespace Modules\Subscription\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use Mockery;
use Modules\Subscription\Contracts\SubscriptionPlanRepositoryInterface;
use Modules\Subscription\DTOs\CreateSubscriptionPlanDTO;
use Modules\Subscription\Enums\SubscriptionPlanStatus;
use Modules\Subscription\Models\SubscriptionPlan;
use Modules\Subscription\Services\SubscriptionPlanService;
use Tests\TestCase;

class SubscriptionPlanServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionPlanService $service;
    private $repository;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(SubscriptionPlanRepositoryInterface::class);
        $this->service    = new SubscriptionPlanService($this->repository);
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

    public function test_paginate_throws_when_per_page_out_of_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->paginate(0);
    }

    public function test_store_persists_plan(): void
    {
        $plan = SubscriptionPlan::factory()->make(['id' => 1]);

        $this->repository->shouldReceive('create')->once()->andReturn($plan);

        $result = $this->service->store(new CreateSubscriptionPlanDTO(
            name:         'Monthly Plan',
            price:        9.99,
            durationDays: 30,
        ));

        $this->assertInstanceOf(SubscriptionPlan::class, $result);
    }

    public function test_activate_throws_when_plan_already_active(): void
    {
        $plan = SubscriptionPlan::factory()->make(['id' => 1, 'status' => SubscriptionPlanStatus::ACTIVE]);

        $this->expectException(LogicException::class);

        $this->service->activate($plan);
    }

    public function test_deactivate_throws_when_plan_already_inactive(): void
    {
        $plan = SubscriptionPlan::factory()->make(['id' => 1, 'status' => SubscriptionPlanStatus::INACTIVE]);

        $this->expectException(LogicException::class);

        $this->service->deactivate($plan);
    }
}
