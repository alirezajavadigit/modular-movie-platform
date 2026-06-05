<?php

namespace Modules\Auth\Repositories;

use Carbon\Carbon;
use Modules\Auth\Contracts\OtpRepositoryInterface;
use Modules\Auth\DTOs\OtpDTO;
use Modules\Auth\Models\Otp;

class OtpRepository implements OtpRepositoryInterface
{
    public function __construct(
        private readonly Otp $model,
    ) {}

    public function create(OtpDTO $dto, Carbon $expiresAt): Otp
    {
        return $this->model->create([
            'user_id' => $dto->userId,
            'code' => $dto->code,
            'channel' => $dto->channel,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findValid(int $userId, string $code): ?Otp
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('code', $code)
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->first();
    }

    public function markUsed(Otp $otp): void
    {
        $otp->update(['used_at' => now()]);
    }

    public function deleteExpired(): void
    {
        $this->model
            ->where('expires_at', '<=', now())
            ->delete();
    }
}
