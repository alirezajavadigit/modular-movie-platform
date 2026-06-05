<?php

namespace Modules\Subscription\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use Mockery;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Subscription\Contracts\SubscriptionPlanRepositoryInterface;
use Modules\Subscription\Contracts\SubscriptionRepositoryInterface;
use Modules\Subscription\DTOs\CreateSubscriptionDTO;
use Modules\Subscription\Enums\SubscriptionPlanStatus;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Subscription;
use Modules\Subscription\Models\SubscriptionPlan;
use Modules\Subscription\Services\SubscriptionService;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionService $service;
    private $subscriptionRepo;
    private $planRepo;
    private $paymentService;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriptionRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
        $this->planRepo         = Mockery::mock(SubscriptionPlanRepositoryInterface::class);
        $this->paymentService   = Mockery::mock(PaymentServiceInterface::class);

        $this->service = new SubscriptionService(
            $this->subscriptionRepo,
            $this->planRepo,
            $this->paymentService,
        );
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

    public function test_subscribe_throws_when_plan_not_found(): void
    {
        $this->planRepo->shouldReceive('findById')->with(99)->once()->andReturn(null);

        $this->expectException(InvalidArgumentException::class);

        $this->service->subscribe(new CreateSubscriptionDTO(
            userId: 1,
            planId: 99,
            driver: 'stripe',
        ));
    }

    public function test_subscribe_throws_when_plan_is_inactive(): void
    {
        $plan = SubscriptionPlan::factory()->make(['id' => 1, 'status' => SubscriptionPlanStatus::INACTIVE]);

        $this->planRepo->shouldReceive('findById')->with(1)->once()->andReturn($plan);

        $this->expectException(LogicException::class);

        $this->service->subscribe(new CreateSubscriptionDTO(
            userId: 1,
            planId: 1,
            driver: 'stripe',
        ));
    }

    public function test_cancel_throws_when_subscription_already_canceled(): void
    {
        $subscription = Subscription::factory()->make([
            'id'      => 1,
            'user_id' => 1,
            'plan_id' => 1,
            'status'  => SubscriptionStatus::CANCELED,
        ]);

        $this->expectException(LogicException::class);

        $this->service->cancel($subscription);
    }

    public function test_activate_throws_when_subscription_not_pending(): void
    {
        $subscription = Subscription::factory()->make([
            'id'      => 1,
            'user_id' => 1,
            'plan_id' => 1,
            'status'  => SubscriptionStatus::ACTIVE,
        ]);

        $this->expectException(LogicException::class);

        $this->service->activate($subscription);
    }
}
