<?php

namespace Modules\Auth\Contracts;

use Carbon\Carbon;
use Modules\Auth\DTOs\OtpDTO;
use Modules\Auth\Models\Otp;

interface OtpRepositoryInterface
{
    public function create(OtpDTO $dto, Carbon $expiresAt): Otp;

    public function findValid(int $userId, string $code): ?Otp;

    public function markUsed(Otp $otp): void;

    public function deleteExpired(): void;
}
