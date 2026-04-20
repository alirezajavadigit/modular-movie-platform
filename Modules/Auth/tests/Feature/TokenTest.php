<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Modules\Auth\Models\User;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    protected function authenticateUser(): array
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        return ['user' => $user, 'token' => $token];
    }

    public function test_me_returns_authenticated_user_through_transformer(): void
    {
        ['user' => $user, 'token' => $token] = $this->authenticateUser();

        $response = $this->getJson(route('auth.me'), [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'phone', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_refresh_returns_a_new_valid_token(): void
    {
        ['token' => $token] = $this->authenticateUser();

        $response = $this->postJson(route('auth.refresh'), [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['token']]);

        $newToken = $response->json('data.token');
        $this->assertNotEmpty($newToken);
    }

    public function test_logout_invalidates_the_current_token(): void
    {
        ['token' => $token] = $this->authenticateUser();

        $logoutResponse = $this->postJson(route('auth.logout'), [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $logoutResponse->assertOk();

        $meResponse = $this->getJson(route('auth.me'), [
            'Authorization' => "Bearer {$token}",
        ]);

        $meResponse->assertStatus(401);
    }

    public function test_protected_routes_return_401_with_invalid_token(): void
    {
        $response = $this->getJson(route('auth.me'), [
            'Authorization' => 'Bearer invalid-token-here',
        ]);

        $response->assertStatus(401);
    }

    public function test_protected_routes_return_401_with_no_token(): void
    {
        $response = $this->getJson(route('auth.me'));

        $response->assertStatus(401);
    }
}
