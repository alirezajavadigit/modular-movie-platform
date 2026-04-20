<?php

namespace Modules\Auth\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\DTOs\RegisterDTO;
use Modules\Auth\DTOs\SocialUserDTO;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\UserRepository;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new UserRepository(new User());
    }

    public function test_find_by_email_returns_correct_user(): void
    {
        $user = User::factory()->create(['email' => 'findme@example.com']);

        $found = $this->repository->findByEmail('findme@example.com');

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_find_by_phone_returns_correct_user(): void
    {
        $user = User::factory()->create(['phone' => '+1112223333']);

        $found = $this->repository->findByPhone('+1112223333');

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_find_by_email_or_phone_resolves_by_either_field(): void
    {
        $emailUser = User::factory()->create(['email' => 'either@example.com']);
        $phoneUser = User::factory()->create(['phone' => '+4445556666']);

        $foundByEmail = $this->repository->findByEmailOrPhone('either@example.com');
        $foundByPhone = $this->repository->findByEmailOrPhone('+4445556666');

        $this->assertEquals($emailUser->id, $foundByEmail->id);
        $this->assertEquals($phoneUser->id, $foundByPhone->id);
    }

    public function test_find_or_create_from_social_creates_new_user(): void
    {
        $dto = new SocialUserDTO(
            provider: 'google',
            providerId: 'g-12345',
            email: 'social-new@example.com',
            name: 'Social User',
            avatar: null,
            token: 'access-token',
            refreshToken: 'refresh-token',
            tokenExpiresAt: now()->addHour(),
        );

        $user = $this->repository->findOrCreateFromSocial($dto);

        $this->assertDatabaseHas('users', [
            'email' => 'social-new@example.com',
            'name' => 'Social User',
        ]);

        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'g-12345',
        ]);
    }

    public function test_find_or_create_from_social_merges_into_existing_user_by_email(): void
    {
        $existingUser = User::factory()->create(['email' => 'merge@example.com']);

        $dto = new SocialUserDTO(
            provider: 'google',
            providerId: 'g-merge-99',
            email: 'merge@example.com',
            name: 'Different Name',
            avatar: null,
            token: 'tok',
            refreshToken: null,
            tokenExpiresAt: null,
        );

        $user = $this->repository->findOrCreateFromSocial($dto);

        $this->assertEquals($existingUser->id, $user->id);
        $this->assertDatabaseCount('users', 1);

        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $existingUser->id,
            'provider' => 'google',
            'provider_user_id' => 'g-merge-99',
        ]);
    }
}
