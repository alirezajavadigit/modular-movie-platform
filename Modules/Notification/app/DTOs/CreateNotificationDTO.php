<?php

namespace Modules\Notification\DTOs;

use Modules\Notification\Enums\NotificationChannel;

readonly class CreateNotificationDTO
{
    public function __construct(
        public string              $notifiableType,
        public int                 $notifiableId,
        public string              $type,
        public NotificationChannel $channel,
        public array               $data,
    ) {}
}
