<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_user_can_login_with_email_and_password(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson(route('auth.login'), [
            'identifier' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => ['token', 'user'],
            ]);
    }

    public function test_user_can_login_with_phone_and_password(): void
    {
        User::factory()->create([
            'phone' => '+9876543210',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson(route('auth.login'), [
            'identifier' => '+9876543210',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success');
    }

    public function test_wrong_password_returns_401(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('correct'),
        ]);

        $response = $this->postJson(route('auth.login'), [
            'identifier' => 'user@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401);
    }

    public function test_non_existent_identifier_returns_401(): void
    {
        $response = $this->postJson(route('auth.login'), [
            'identifier' => 'ghost@example.com',
            'password' => 'anything',
        ]);

        $response->assertStatus(401);
    }

    public function test_successful_response_contains_token_and_user_via_transformer(): void
    {
        User::factory()->create([
            'email' => 'transform@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson(route('auth.login'), [
            'identifier' => 'transform@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email', 'phone', 'created_at', 'updated_at'],
                ],
            ]);
    }
}
