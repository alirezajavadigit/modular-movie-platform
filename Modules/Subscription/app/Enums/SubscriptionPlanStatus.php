<?php

namespace Modules\Subscription\Enums;

enum SubscriptionPlanStatus: string
{
    case ACTIVE   = 'active';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE   => 'Active',
            self::INACTIVE => 'Inactive',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this === self::INACTIVE;
    }

    public static function default(): self
    {
        return self::ACTIVE;
    }
}
