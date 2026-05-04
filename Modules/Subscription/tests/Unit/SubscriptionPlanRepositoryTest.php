<?php

namespace Modules\Subscription\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Subscription\DTOs\CreateSubscriptionPlanDTO;
use Modules\Subscription\Enums\SubscriptionPlanStatus;
use Modules\Subscription\Models\SubscriptionPlan;
use Modules\Subscription\Repositories\SubscriptionPlanRepository;
use Tests\TestCase;

class SubscriptionPlanRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionPlanRepository $repository;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new SubscriptionPlanRepository(new SubscriptionPlan());
    }

    public function test_find_by_id_returns_plan_when_found(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $result = $this->repository->findById($plan->id);

        $this->assertInstanceOf(SubscriptionPlan::class, $result);
        $this->assertEquals($plan->id, $result->id);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function test_create_persists_plan(): void
    {
        $dto = new CreateSubscriptionPlanDTO(
            name:         'Monthly Plan',
            price:        9.99,
            durationDays: 30,
            description:  'Access to all movies for 30 days.',
        );

        $result = $this->repository->create($dto);

        $this->assertInstanceOf(SubscriptionPlan::class, $result);
        $this->assertEquals('Monthly Plan', $result->name);
        $this->assertEquals(30, $result->duration_days);
        $this->assertEquals(SubscriptionPlanStatus::ACTIVE, $result->status);
    }

    public function test_get_active_returns_only_active_plans(): void
    {
        SubscriptionPlan::factory()->active()->count(2)->create();
        SubscriptionPlan::factory()->inactive()->count(1)->create();

        $result = $this->repository->getActive();

        $this->assertCount(2, $result);
        $result->each(fn($plan) => $this->assertTrue($plan->status->isActive()));
    }

    public function test_delete_soft_deletes_plan(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $this->repository->delete($plan->id);

        $this->assertSoftDeleted('subscription_plans', ['id' => $plan->id]);
    }
}
