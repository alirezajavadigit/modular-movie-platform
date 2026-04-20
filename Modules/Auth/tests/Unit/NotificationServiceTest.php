<?php

namespace Modules\Auth\Tests\Unit;

use Mockery;
use Modules\Auth\Contracts\Notification\NotificationChannelInterface;
use Modules\Auth\Services\Notification\Channels\EmailChannel;
use Modules\Auth\Services\Notification\Channels\SmsChannel;
use Modules\Auth\Services\Notification\NotificationService;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    public function test_resolves_email_channel_when_config_is_email(): void
    {
        config(['auth-module.notification_channel' => 'email']);

        $channel = app(NotificationChannelInterface::class);

        $this->assertInstanceOf(EmailChannel::class, $channel);
    }

    public function test_resolves_sms_channel_when_config_is_sms(): void
    {
        config(['auth-module.notification_channel' => 'sms']);

        $channel = app(NotificationChannelInterface::class);

        $this->assertInstanceOf(SmsChannel::class, $channel);
    }

    public function test_send_delegates_to_the_injected_channel(): void
    {
        $channel = Mockery::mock(NotificationChannelInterface::class);
        $channel->shouldReceive('send')
            ->once()
            ->with('recipient@example.com', 'Your code is 123456');

        $service = new NotificationService($channel);
        $service->send('recipient@example.com', 'Your code is 123456');
    }
}
