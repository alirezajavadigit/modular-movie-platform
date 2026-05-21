<?php

namespace Modules\Subscription\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Mockery;
use Modules\Auth\Models\User;
use Modules\Subscription\Contracts\SubscriptionPlanRepositoryInterface;
use Modules\Subscription\Contracts\SubscriptionRepositoryInterface;
use Modules\Subscription\Contracts\SubscriptionServiceInterface;
use Modules\Subscription\Models\Subscription;
use Modules\Subscription\Models\SubscriptionPlan;
use Modules\Subscription\Policies\SubscriptionPolicy;
use Modules\Subscription\Repositories\SubscriptionPlanRepository;
use Modules\Subscription\Repositories\SubscriptionRepository;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SubscriptionFeatureTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionServiceInterface $service;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['router']->group(['middleware' => 'api'], __DIR__ . '/../../routes/api.php');

        Route::bind('subscription', fn(string $value) => Subscription::withTrashed()->findOrFail($value));

        $this->app->bind(SubscriptionRepositoryInterface::class, SubscriptionRepository::class);
        $this->app->bind(SubscriptionPlanRepositoryInterface::class, SubscriptionPlanRepository::class);

        Gate::policy(Subscription::class, SubscriptionPolicy::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->service = Mockery::mock(SubscriptionServiceInterface::class);
        $this->app->instance(SubscriptionServiceInterface::class, $this->service);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    private function asAdmin(): static
    {
        $user = User::factory()->create();

        Permission::firstOrCreate(['name' => 'subscriptions.viewAny', 'guard_name' => 'api']);

        $user->givePermissionTo('subscriptions.viewAny');

        $this->actingAs($user, 'api');

        return $this;
    }

    public function test_admin_index_returns_paginated_subscriptions(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 15, 1, ['path' => 'http://localhost']);

        $this->service->shouldReceive('paginate')->once()->andReturn($paginator);

        $this->asAdmin()
            ->getJson('api/v1/admin/subscriptions')
            ->assertOk();
    }

    public function test_user_index_returns_own_subscriptions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        $paginator = new LengthAwarePaginator([], 0, 15, 1, ['path' => 'http://localhost']);

        $this->service->shouldReceive('paginateForUser')->with($user->id, 15)->once()->andReturn($paginator);

        $this->getJson('api/v1/subscriptions')
            ->assertOk();
    }

    public function test_subscribe_returns_payment_url(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        $this->service->shouldReceive('subscribe')->once()->andReturn('https://payment.gateway/redirect');

        $this->postJson('api/v1/subscriptions/subscribe', [
            'plan_id' => 1,
            'driver'  => 'stripe',
        ])->assertOk();
    }

    public function test_cancel_cancels_own_subscription(): void
    {
        $user = User::factory()->create();

        Permission::firstOrCreate(['name' => 'subscriptions.cancel', 'guard_name' => 'api']);

        $this->actingAs($user, 'api');

        $plan         = SubscriptionPlan::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        $this->service->shouldReceive('cancel')->once()->andReturn($subscription);

        $this->patchJson("api/v1/subscriptions/{$subscription->id}/cancel")
            ->assertOk();
    }

    public function test_cancel_is_forbidden_for_other_users_subscription(): void
    {
        $owner  = User::factory()->create();
        $intruder = User::factory()->create();

        Permission::firstOrCreate(['name' => 'subscriptions.cancel', 'guard_name' => 'api']);

        $this->actingAs($intruder, 'api');

        $plan         = SubscriptionPlan::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $owner->id,
            'plan_id' => $plan->id,
        ]);

        $this->patchJson("api/v1/subscriptions/{$subscription->id}/cancel")
            ->assertForbidden();
    }

    public function test_admin_destroy_deletes_subscription(): void
    {
        $user = User::factory()->create();

        Permission::firstOrCreate(['name' => 'subscriptions.delete', 'guard_name' => 'api']);
        $user->givePermissionTo('subscriptions.delete');

        $this->actingAs($user, 'api');

        $plan         = SubscriptionPlan::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        $this->service->shouldReceive('delete')->once()->andReturn(true);

        $this->deleteJson("api/v1/admin/subscriptions/{$subscription->id}")
            ->assertNoContent();
    }
}
