<?php

namespace Modules\Payment\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Modules\Auth\Models\User;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Payment\Models\Payment;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PaymentFeatureTest extends TestCase
{
    use RefreshDatabase;

    private PaymentServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutExceptionHandling();
        $this->service = Mockery::mock(PaymentServiceInterface::class);
        $this->app->instance(PaymentServiceInterface::class, $this->service);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    private function asAdmin(): static
    {
        $user = User::factory()->create();

        $permissions = [
            'payments.viewAny',
            'payments.view',
            'payments.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        $user->givePermissionTo($permissions);

        $this->actingAs($user, 'api');

        return $this;
    }

    public function test_index_returns_paginated_list(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 15, 1, ['path' => 'http://localhost']);

        $this->service->shouldReceive('paginate')->once()->andReturn($paginator);

        $this->asAdmin()
            ->getJson('api/v1/admin/payments')
            ->assertOk();
    }

    public function test_show_returns_payment(): void
    {
        $payment = Payment::factory()->create();
        $this->service->shouldReceive('findById')->with($payment->id)->once()->andReturn($payment);

        $this->asAdmin()
            ->getJson("api/v1/admin/payments/{$payment->id}")
            ->assertOk();
    }

    public function test_destroy_deletes_payment(): void
    {
        $payment = Payment::factory()->create();
        $this->service->shouldReceive('delete')->with($payment->id)->once()->andReturn(true);

        $this->asAdmin()
            ->deleteJson("api/v1/admin/payments/{$payment->id}")
            ->assertNoContent();
    }
}
