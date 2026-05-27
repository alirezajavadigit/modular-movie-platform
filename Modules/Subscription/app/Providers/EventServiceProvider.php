<?php

namespace Modules\Subscription\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Events\PaymentSucceeded;
use Modules\Subscription\Listeners\ActivateSubscription;
use Modules\Subscription\Listeners\FailSubscription;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentSucceeded::class => [
            ActivateSubscription::class,
        ],
        PaymentFailed::class => [
            FailSubscription::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;
}
