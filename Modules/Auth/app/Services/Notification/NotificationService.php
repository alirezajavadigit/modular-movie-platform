<?php

namespace Modules\Auth\Services\Notification;

use Modules\Auth\Contracts\Notification\NotificationChannelInterface;

class NotificationService
{
    public function __construct(
        private readonly NotificationChannelInterface $channel,
    ) {}

    public function send(string $recipient, string $message): void
    {
        $this->channel->send($recipient, $message);
    }
}
