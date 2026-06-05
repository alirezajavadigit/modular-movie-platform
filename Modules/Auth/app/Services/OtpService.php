<?php

namespace Modules\Auth\Services;

use InvalidArgumentException;
use Modules\Auth\Contracts\OtpRepositoryInterface;
use Modules\Auth\Contracts\OtpServiceInterface;
use Modules\Auth\DTOs\OtpDTO;
use Modules\Auth\Jobs\SendOtpNotificationJob;
use Modules\Auth\Models\User;

class OtpService implements OtpServiceInterface
{
    private const array CHANNEL_MAP = [
        'phone' => 'sms',
        'sms' => 'sms',
        'email' => 'email',
    ];

    public function __construct(
        private readonly OtpRepositoryInterface $otpRepository,
    ) {}

    public function generate(User $user, string $channel): OtpDTO
    {
        $normalizedChannel = $this->normalizeChannel($channel);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $dto = new OtpDTO(
            userId: $user->id,
            code: $code,
            channel: $normalizedChannel,
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
        $normalizedChannel = $this->normalizeChannel($channel);

        $otpDto = $this->generate($user, $normalizedChannel);

        $recipient = $this->resolveRecipient($user, $normalizedChannel);

        SendOtpNotificationJob::dispatch($recipient, $otpDto->code);
    }

    private function normalizeChannel(string $channel): string
    {
        return self::CHANNEL_MAP[$channel]
            ?? throw new InvalidArgumentException("Invalid OTP channel: {$channel}");
    }

    private function resolveRecipient(User $user, string $normalizedChannel): string
    {
        return match ($normalizedChannel) {
            'email' => $user->email,
            'sms' => $user->phone,
            default => throw new InvalidArgumentException("No recipient for channel: {$normalizedChannel}"),
        };
    }
}
