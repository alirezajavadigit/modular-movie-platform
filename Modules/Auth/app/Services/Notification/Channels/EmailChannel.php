<?php

namespace Modules\Auth\Services\Notification\Channels;

use Illuminate\Support\Facades\Mail;
use Modules\Auth\Contracts\Notification\NotificationChannelInterface;

class EmailChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $message): void
    {
        Mail::raw($message, function ($mail) use ($recipient) {
            $mail->to($recipient)
                ->subject(__('auth-module::messages.otp_subject'));
        });
    }
}
