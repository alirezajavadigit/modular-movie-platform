<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Queue;
use Modules\Auth\Jobs\SendOtpNotificationJob;
use Modules\Auth\Models\User;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_user_can_register_with_email(): void
    {
        Queue::fake();

        $response = $this->postJson(route('auth.register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => ['token', 'user'],
            ])
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    }

    public function test_user_can_register_with_phone(): void
    {
        Queue::fake();

        $response = $this->postJson(route('auth.register'), [
            'name' => 'Jane Doe',
            'phone' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('users', [
            'phone' => '+1234567890',
        ]);
    }

    public function test_duplicate_email_is_rejected_with_422(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson(route('auth.register'), [
            'name' => 'Test',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_duplicate_phone_is_rejected_with_422(): void
    {
        User::factory()->create(['phone' => '+1234567890']);

        $response = $this->postJson(route('auth.register'), [
            'name' => 'Test',
            'phone' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['errors' => ['phone']]);
    }

    public function test_otp_notification_job_is_dispatched_after_registration(): void
    {
        Queue::fake();

        $this->postJson(route('auth.register'), [
            'name' => 'OTP User',
            'email' => 'otp@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        Queue::assertPushedOn('notifications', SendOtpNotificationJob::class);
    }

    public function test_missing_name_returns_validation_error(): void
    {
        $response = $this->postJson(route('auth.register'), [
            'email' => 'noname@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_response_contains_jwt_token(): void
    {
        Queue::fake();

        $response = $this->postJson(route('auth.register'), [
            'name' => 'Token User',
            'email' => 'token@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $this->assertNotEmpty($response->json('data.token'));
    }
}
