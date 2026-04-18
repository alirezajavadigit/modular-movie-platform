<?php

namespace Modules\Auth\Contracts\Notification;

interface NotificationChannelInterface
{
    public function send(string $recipient, string $message): void;
}
