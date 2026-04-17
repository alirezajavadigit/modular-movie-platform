<?php

namespace Modules\Auth\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Services\Notification\NotificationService;

class SendOtpNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly string $recipient,
        public readonly string $otp,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        app(NotificationService::class)->send(
            $this->recipient,
            __('auth-module::messages.otp_message', ['code' => $this->otp]),
        );
    }
}
