<?php

namespace Modules\Auth\Services;

use Modules\Auth\Contracts\OtpRepositoryInterface;
use Modules\Auth\Contracts\OtpServiceInterface;
use Modules\Auth\DTOs\OtpDTO;
use Modules\Auth\Jobs\SendOtpNotificationJob;
use Modules\Auth\Models\User;

class OtpService implements OtpServiceInterface
{
    public function __construct(
        private readonly OtpRepositoryInterface $otpRepository,
    ) {}

    public function generate(User $user, string $channel): OtpDTO
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $dto = new OtpDTO(
            userId: $user->id,
            code: $code,
            channel: $channel,
        );

        $expiresAt = now()->addMinutes(config('auth-module.otp_ttl', 5));

        $this->otpRepository->create($dto, $expiresAt);

        return $dto;
    }

    public function verify(User $user, string $code): bool
    {
        $otp = $this->otpRepository->findValid($user->id, $code);

        if (! $otp) {
            return false;
        }

        $this->otpRepository->markUsed($otp);

        return true;
    }

    public function dispatch(User $user, string $channel): void
    {
        $otpChannel = $channel === 'phone' ? 'sms' : $channel;

        $otpDto = $this->generate($user, $otpChannel);

        $recipient = $otpChannel === 'email' ? $user->email : $user->phone;

        SendOtpNotificationJob::dispatch($recipient, $otpDto->code);
    }
}
