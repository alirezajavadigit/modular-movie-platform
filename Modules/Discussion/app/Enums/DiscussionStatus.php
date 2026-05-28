<?php

namespace Modules\Discussion\Enums;

enum DiscussionStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING  => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    public static function default(): self
    {
        return self::PENDING;
    }
}
