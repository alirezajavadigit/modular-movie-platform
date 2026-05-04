<?php

namespace Modules\Subscription\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Modules\Auth\Models\User;
use Modules\Subscription\Contracts\SubscriptionPlanRepositoryInterface;
use Modules\Subscription\Contracts\SubscriptionPlanServiceInterface;
use Modules\Subscription\Models\SubscriptionPlan;
use Modules\Subscription\Policies\SubscriptionPlanPolicy;
use Modules\Subscription\Repositories\SubscriptionPlanRepository;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SubscriptionPlanFeatureTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionPlanServiceInterface $service;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['router']->group(['middleware' => 'api'], __DIR__ . '/../../routes/api.php');

        $this->app->bind(SubscriptionPlanRepositoryInterface::class, SubscriptionPlanRepository::class);

        Gate::policy(SubscriptionPlan::class, SubscriptionPlanPolicy::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->service = Mockery::mock(SubscriptionPlanServiceInterface::class);
        $this->app->instance(SubscriptionPlanServiceInterface::class, $this->service);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    private function asAdmin(): static
    {
        $user = User::factory()->create();

        Permission::firstOrCreate(['name' => 'subscription_plans.viewAny', 'guard_name' => 'api']);

        $user->givePermissionTo('subscription_plans.viewAny');

        $this->actingAs($user, 'api');

        return $this;
    }

    public function test_index_returns_paginated_plans(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 15, 1, ['path' => 'http://localhost']);

        $this->service->shouldReceive('paginate')->once()->andReturn($paginator);

        $this->asAdmin()
            ->getJson('api/v1/subscription-plans')
            ->assertOk();
    }

    public function test_show_returns_plan(): void
    {
        $plan = SubscriptionPlan::factory()->make(['id' => 1]);

        $this->service->shouldReceive('findById')->with(1)->once()->andReturn($plan);

        $this->getJson('api/v1/subscription-plans/1')
            ->assertOk();
    }

    public function test_store_creates_plan(): void
    {
        $user = User::factory()->create();

        Permission::firstOrCreate(['name' => 'subscription_plans.create', 'guard_name' => 'api']);

        $user->givePermissionTo('subscription_plans.create');

        $this->actingAs($user, 'api');

        $plan = SubscriptionPlan::factory()->make(['id' => 1]);

        $this->service->shouldReceive('store')->once()->andReturn($plan);

        $this->postJson('api/v1/admin/subscription-plans', [
            'name'          => 'Monthly Plan',
            'price'         => 9.99,
            'duration_days' => 30,
        ])->assertCreated();
    }

    public function test_destroy_deletes_plan(): void
    {
        $user = User::factory()->create();

        Permission::firstOrCreate(['name' => 'subscription_plans.delete', 'guard_name' => 'api']);
        $user->givePermissionTo('subscription_plans.delete');

        $this->actingAs($user, 'api');

        $plan = SubscriptionPlan::factory()->create();

        $this->service->shouldReceive('delete')->with($plan->id)->once()->andReturn(true);

        $this->deleteJson("api/v1/admin/subscription-plans/{$plan->id}")
            ->assertNoContent();
    }
}
