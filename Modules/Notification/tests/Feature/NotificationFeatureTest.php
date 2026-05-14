<?php

namespace Modules\Notification\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Modules\Auth\Models\User;
use Modules\Notification\Contracts\NotificationServiceInterface;
use Modules\Notification\Models\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private NotificationServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->service = Mockery::mock(NotificationServiceInterface::class);
        $this->app->instance(NotificationServiceInterface::class, $this->service);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    private function asAdmin(): static
    {
        $user = User::factory()->create();

        Permission::firstOrCreate(['name' => 'notifications.viewAny', 'guard_name' => 'api']);

        $user->givePermissionTo('notifications.viewAny');

        $this->actingAs($user, 'api');

        return $this;
    }

    public function test_index_returns_paginated_notifications(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 15, 1, ['path' => 'http://localhost']);

        $this->service->shouldReceive('paginate')->once()->andReturn($paginator);

        $this->asAdmin()
            ->getJson('api/v1/admin/notifications')
            ->assertOk();
    }

    public function test_show_returns_single_notification(): void
    {
        $user         = User::factory()->create();
        $notification = Notification::factory()->create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
        ]);

        Permission::firstOrCreate(['name' => 'notifications.view', 'guard_name' => 'api']);
        $user->givePermissionTo('notifications.view');

        $this->actingAs($user, 'api')
            ->getJson("api/v1/admin/notifications/{$notification->id}")
            ->assertOk();
    }

    public function test_destroy_deletes_notification(): void
    {
        $user         = User::factory()->create();
        $notification = Notification::factory()->create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
        ]);

        $this->service->shouldReceive('delete')->with($notification->id)->once()->andReturn(true);

        Permission::firstOrCreate(['name' => 'notifications.delete', 'guard_name' => 'api']);
        $user->givePermissionTo('notifications.delete');

        $this->actingAs($user, 'api')
            ->deleteJson("api/v1/admin/notifications/{$notification->id}")
            ->assertNoContent();
    }
}
