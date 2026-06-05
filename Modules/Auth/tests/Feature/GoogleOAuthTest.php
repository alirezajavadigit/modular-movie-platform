<?php

namespace Modules\Auth\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Modules\Auth\Models\User;
use Tests\TestCase;

class GoogleOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function mockSocialiteUser(string $email, string $name = 'Google User'): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-id-123');
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://avatar.url');
        $socialiteUser->token = 'google-token';
        $socialiteUser->refreshToken = 'google-refresh';
        $socialiteUser->expiresIn = 3600;

        $driver = Mockery::mock(GoogleProvider::class);
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($driver);
    }

    public function test_callback_creates_new_user_when_email_not_found(): void
    {
        $this->mockSocialiteUser('newgoogle@example.com', 'New Google');

        $response = $this->getJson(route('auth.oauth.google.callback'));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $this->assertDatabaseHas('users', [
            'email' => 'newgoogle@example.com',
            'name' => 'New Google',
        ]);

        $this->assertDatabaseHas('oauth_providers', [
            'provider' => 'google',
            'provider_user_id' => 'google-id-123',
        ]);
    }

    public function test_callback_merges_into_existing_account_when_email_matches(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing User',
        ]);

        $this->mockSocialiteUser('existing@example.com', 'Google Name');

        $response = $this->getJson(route('auth.oauth.google.callback'));

        $response->assertOk();

        $this->assertDatabaseCount('users', 1);

        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $existingUser->id,
            'provider' => 'google',
        ]);
    }

    public function test_callback_returns_jwt_token_on_success(): void
    {
        $this->mockSocialiteUser('jwt@example.com');

        $response = $this->getJson(route('auth.oauth.google.callback'));

        $response->assertOk();

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);
    }
}
