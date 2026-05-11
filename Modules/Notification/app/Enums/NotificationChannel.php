<?php

namespace Modules\Notification\Enums;

enum NotificationChannel: string
{
    case DATABASE = 'database';
    case EMAIL    = 'email';
    case SMS      = 'sms';
    case PUSH     = 'push';

    public function label(): string
    {
        return match ($this) {
            self::DATABASE => 'Database',
            self::EMAIL    => 'Email',
            self::SMS      => 'SMS',
            self::PUSH     => 'Push',
        };
    }

    public function isDatabase(): bool
    {
        return $this === self::DATABASE;
    }

    public function isEmail(): bool
    {
        return $this === self::EMAIL;
    }

    public function isSms(): bool
    {
        return $this === self::SMS;
    }

    public function isPush(): bool
    {
        return $this === self::PUSH;
    }

    public static function default(): self
    {
        return self::DATABASE;
    }
}
