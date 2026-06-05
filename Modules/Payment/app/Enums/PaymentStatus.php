<?php

namespace Modules\Payment\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING  => 'Pending',
            self::SUCCESS  => 'Success',
            self::FAILED   => 'Failed',
            self::CANCELED => 'Canceled',
            self::REFUNDED => 'Refunded',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    public function isCanceled(): bool
    {
        return $this === self::CANCELED;
    }

    public function isRefunded(): bool
    {
        return $this === self::REFUNDED;
    }

    public static function default(): self
    {
        return self::PENDING;
    }
}
