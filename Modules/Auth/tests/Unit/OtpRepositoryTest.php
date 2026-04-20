<?php

namespace Modules\Auth\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\DTOs\OtpDTO;
use Modules\Auth\Models\Otp;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\OtpRepository;
use Tests\TestCase;

class OtpRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private OtpRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new OtpRepository(new Otp());
    }

    public function test_create_persists_otp_with_correct_channel_and_expiry(): void
    {
        $user = User::factory()->create();

        $dto = new OtpDTO(
            userId: $user->id,
            code: '112233',
            channel: 'email',
        );

        $expiresAt = now()->addMinutes(5)->startOfSecond();

        $otp = $this->repository->create($dto, $expiresAt);

        $this->assertDatabaseHas('otps', [
            'user_id' => $user->id,
            'code' => '112233',
            'channel' => 'email',
        ]);

        $this->assertTrue($otp->expires_at->startOfSecond()->equalTo($expiresAt));
    }

    public function test_find_valid_returns_null_for_expired_otp(): void
    {
        $user = User::factory()->create();

        Otp::create([
            'user_id' => $user->id,
            'code' => '999999',
            'channel' => 'email',
            'expires_at' => now()->subMinute(),
        ]);

        $result = $this->repository->findValid($user->id, '999999');

        $this->assertNull($result);
    }

    public function test_find_valid_returns_null_for_used_otp(): void
    {
        $user = User::factory()->create();

        Otp::create([
            'user_id' => $user->id,
            'code' => '888888',
            'channel' => 'sms',
            'expires_at' => now()->addMinutes(5),
            'used_at' => now(),
        ]);

        $result = $this->repository->findValid($user->id, '888888');

        $this->assertNull($result);
    }

    public function test_mark_used_sets_used_at_timestamp(): void
    {
        $user = User::factory()->create();

        $otp = Otp::create([
            'user_id' => $user->id,
            'code' => '777777',
            'channel' => 'email',
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->assertNull($otp->used_at);

        $this->repository->markUsed($otp);

        $otp->refresh();

        $this->assertNotNull($otp->used_at);
    }
}
