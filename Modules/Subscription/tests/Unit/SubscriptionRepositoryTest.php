<?php

namespace Modules\Subscription\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Subscription\DTOs\CreateSubscriptionDTO;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Subscription;
use Modules\Subscription\Models\SubscriptionPlan;
use Modules\Subscription\Repositories\SubscriptionRepository;
use Tests\TestCase;

class SubscriptionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionRepository $repository;
    private User $user;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new SubscriptionRepository(new Subscription());
        $this->user       = User::factory()->create();
    }

    public function test_find_by_id_returns_subscription_when_found(): void
    {
        $plan         = SubscriptionPlan::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
        ]);

        $result = $this->repository->findById($subscription->id);

        $this->assertInstanceOf(Subscription::class, $result);
        $this->assertEquals($subscription->id, $result->id);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function test_create_persists_subscription(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $dto = new CreateSubscriptionDTO(
            userId: $this->user->id,
            planId: $plan->id,
            driver: 'stripe',
        );

        $result = $this->repository->create($dto);

        $this->assertInstanceOf(Subscription::class, $result);
        $this->assertEquals($this->user->id, $result->user_id);
        $this->assertEquals($plan->id, $result->plan_id);
        $this->assertEquals(SubscriptionStatus::PENDING, $result->status);
    }

    public function test_get_all_for_user_returns_only_user_subscriptions(): void
    {
        $plan       = SubscriptionPlan::factory()->create();
        $otherUser  = User::factory()->create();

        Subscription::factory()->count(2)->create(['user_id' => $this->user->id, 'plan_id' => $plan->id]);
        Subscription::factory()->count(3)->create(['user_id' => $otherUser->id, 'plan_id' => $plan->id]);

        $result = $this->repository->getAllForUser($this->user->id);

        $this->assertCount(2, $result);
        $result->each(fn($sub) => $this->assertEquals($this->user->id, $sub->user_id));
    }

    public function test_delete_soft_deletes_subscription(): void
    {
        $plan         = SubscriptionPlan::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
        ]);

        $this->repository->delete($subscription->id);

        $this->assertSoftDeleted('subscriptions', ['id' => $subscription->id]);
    }
}
