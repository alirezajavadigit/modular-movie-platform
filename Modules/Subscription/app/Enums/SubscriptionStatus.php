<?php

namespace Modules\Subscription\Enums;

enum SubscriptionStatus: string
{
    case PENDING  = 'pending';
    case ACTIVE   = 'active';
    case EXPIRED  = 'expired';
    case CANCELED = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING  => 'Pending',
            self::ACTIVE   => 'Active',
            self::EXPIRED  => 'Expired',
            self::CANCELED => 'Canceled',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isExpired(): bool
    {
        return $this === self::EXPIRED;
    }

    public function isCanceled(): bool
    {
        return $this === self::CANCELED;
    }

    public static function default(): self
    {
        return self::PENDING;
    }
}
