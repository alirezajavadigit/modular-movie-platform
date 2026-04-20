<?php

namespace Modules\Auth\Tests\Unit;

use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Modules\Auth\Contracts\OtpRepositoryInterface;
use Modules\Auth\Jobs\SendOtpNotificationJob;
use Modules\Auth\Models\Otp;
use Modules\Auth\Models\User;
use Modules\Auth\Services\OtpService;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    private MockInterface $otpRepository;

    private OtpService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->otpRepository = Mockery::mock(OtpRepositoryInterface::class);
        $this->service = new OtpService($this->otpRepository);
    }

    public function test_generated_code_is_exactly_6_numeric_digits(): void
    {
        $user = new User();
        $user->id = 1;

        $this->otpRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn(new Otp());

        $dto = $this->service->generate($user, 'email');

        $this->assertMatchesRegularExpression('/^\d{6}$/', $dto->code);
    }

    public function test_generated_otp_expires_after_config_ttl_minutes(): void
    {
        config(['auth-module.otp_ttl' => 10]);

        $this->travelTo(now());

        $user = new User();
        $user->id = 1;

        $expectedExpiry = now()->addMinutes(10);

        $this->otpRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($dto, $expiresAt) use ($expectedExpiry) {
                return $expiresAt->equalTo($expectedExpiry);
            })
            ->andReturn(new Otp());

        $this->service->generate($user, 'email');
    }

    public function test_dispatch_calls_send_otp_notification_job(): void
    {
        Queue::fake();

        $user = new User();
        $user->id = 1;
        $user->email = 'test@example.com';

        $this->otpRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn(new Otp());

        $this->service->dispatch($user, 'email');

        Queue::assertPushedOn('notifications', SendOtpNotificationJob::class, function ($job) {
            return $job->recipient === 'test@example.com';
        });
    }
}
