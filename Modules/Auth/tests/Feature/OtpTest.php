<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Contracts\OtpServiceInterface;
use Modules\Auth\Models\Otp;
use Modules\Auth\Models\User;
use Tests\TestCase;

class OtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_otp_marks_account_as_verified(): void
    {
        $user = User::factory()->unverified()->create();

        $otpService = app(OtpServiceInterface::class);
        $otpDto = $otpService->generate($user, 'email');

        $result = $otpService->verify($user, $otpDto->code);

        $this->assertTrue($result);
    }

    public function test_expired_otp_returns_false(): void
    {
        $user = User::factory()->create();

        $otp = Otp::create([
            'user_id' => $user->id,
            'code' => '123456',
            'channel' => 'email',
            'expires_at' => now()->subMinutes(10),
        ]);

        $otpService = app(OtpServiceInterface::class);
        $result = $otpService->verify($user, '123456');

        $this->assertFalse($result);
    }

    public function test_already_used_otp_returns_false(): void
    {
        $user = User::factory()->create();

        $otp = Otp::create([
            'user_id' => $user->id,
            'code' => '654321',
            'channel' => 'email',
            'expires_at' => now()->addMinutes(5),
            'used_at' => now(),
        ]);

        $otpService = app(OtpServiceInterface::class);
        $result = $otpService->verify($user, '654321');

        $this->assertFalse($result);
    }

    public function test_otp_for_wrong_user_is_rejected(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $otpService = app(OtpServiceInterface::class);
        $otpDto = $otpService->generate($user1, 'email');

        $result = $otpService->verify($user2, $otpDto->code);

        $this->assertFalse($result);
    }
}
