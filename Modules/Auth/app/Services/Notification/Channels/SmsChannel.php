<?php

namespace Modules\Auth\Services\Notification\Channels;

use Illuminate\Support\Facades\Log;
use Modules\Auth\Contracts\Notification\NotificationChannelInterface;

class SmsChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $message): void
    {
        Log::channel('stack')->info('SMS OTP dispatched', [
            'recipient' => $recipient,
            'message' => $message,
        ]);
    }
}
