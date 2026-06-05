<?php

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Modules\Auth\Models\User;
use Modules\User\Contracts\UserServiceInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserFeatureTest extends TestCase
{
    use RefreshDatabase;

    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->service = Mockery::mock(UserServiceInterface::class);
        $this->app->instance(UserServiceInterface::class, $this->service);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function actingAsUserWith(array $permissions = []): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'api');
            $user->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($user, 'api');

        return $user;
    }

    private function paginator(array $items = []): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, count($items), 15, 1, ['path' => 'http://localhost']);
    }

    public function test_guest_cannot_list_users(): void
    {
        $this->getJson('/api/v1/admin/users')->assertStatus(401);
    }

    public function test_user_without_permission_cannot_list_users(): void
    {
        $this->actingAsUserWith();

        $this->getJson('/api/v1/admin/users')->assertStatus(403);
    }

    public function test_index_returns_paginated_users(): void
    {
        $this->actingAsUserWith(['users.viewAny']);

        $this->service->shouldReceive('paginate')->once()->andReturn($this->paginator());

        $this->getJson('/api/v1/admin/users')->assertStatus(200);
    }

    public function test_store_creates_user(): void
    {
        $this->actingAsUserWith(['users.create']);
        $created = User::factory()->create();

        $this->service->shouldReceive('store')->once()->andReturn($created);

        $this->postJson('/api/v1/admin/users', [
            'name'     => 'Jane Doe',
            'email'    => 'jane@example.com',
            'password' => 'secret-password',
        ])->assertStatus(201);
    }

    public function test_store_validates_input(): void
    {
        $this->actingAsUserWith(['users.create']);

        $this->postJson('/api/v1/admin/users', [])->assertStatus(422);
    }

    public function test_show_returns_user(): void
    {
        $this->actingAsUserWith(['users.view']);
        $target = User::factory()->create();

        $this->service->shouldReceive('findById')->once()->with($target->id)->andReturn($target);

        $this->getJson("/api/v1/admin/users/{$target->id}")->assertStatus(200);
    }

    public function test_update_modifies_user(): void
    {
        $this->actingAsUserWith(['users.update']);
        $target = User::factory()->create();

        $this->service->shouldReceive('update')->once()->andReturn($target);

        $this->putJson("/api/v1/admin/users/{$target->id}", [
            'name' => 'Updated Name',
        ])->assertStatus(200);
    }

    public function test_destroy_deletes_user(): void
    {
        $this->actingAsUserWith(['users.delete']);
        $target = User::factory()->create();

        $this->service->shouldReceive('delete')->once()->with($target->id)->andReturnTrue();

        $this->deleteJson("/api/v1/admin/users/{$target->id}")->assertStatus(204);
    }

    public function test_user_without_permission_cannot_delete(): void
    {
        $this->actingAsUserWith();
        $target = User::factory()->create();

        $this->deleteJson("/api/v1/admin/users/{$target->id}")->assertStatus(403);
    }
}
