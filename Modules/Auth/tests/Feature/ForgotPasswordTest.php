<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Queue;
use Modules\Auth\Jobs\SendOtpNotificationJob;
use Modules\Auth\Models\User;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_otp_job_dispatched_on_valid_identifier(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'forgot@example.com']);

        $response = $this->postJson(route('auth.forgot-password'), [
            'identifier' => 'forgot@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        Queue::assertPushedOn('notifications', SendOtpNotificationJob::class);
    }

    public function test_unknown_identifier_returns_422(): void
    {
        $response = $this->postJson(route('auth.forgot-password'), [
            'identifier' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_valid_otp_and_new_password_updates_password(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'oldpassword',
        ]);

        $this->postJson(route('auth.forgot-password'), [
            'identifier' => 'reset@example.com',
        ]);

        $this->assertDatabaseHas('otps', [
            'user_id' => $user->id,
            'channel' => 'email',
        ]);
    }
}
