<?php

namespace Modules\Notification\DTOs;

use Modules\Notification\Enums\NotificationChannel;

readonly class UpdateNotificationDTO
{
    public function __construct(
        public ?string              $type,
        public ?NotificationChannel $channel,
        public ?array               $data,
    ) {}
}
