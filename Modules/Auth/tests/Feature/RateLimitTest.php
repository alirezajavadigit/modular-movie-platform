<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Auth\Models\User;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_login_endpoint_returns_429_after_10_attempts(): void
    {
        User::factory()->create(['email' => 'rate@example.com']);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('auth.login'), [
                'identifier' => 'rate@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->postJson(route('auth.login'), [
            'identifier' => 'rate@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
    }

    public function test_register_endpoint_returns_429_after_5_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('auth.register'), [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
        }

        $response = $this->postJson(route('auth.register'), [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(429);
    }
}
