<?php

declare(strict_types=1);

namespace Modules\Subscription\Listeners;

use Modules\Payment\Events\PaymentSucceeded;
use Modules\Subscription\Models\Subscription;

final class ActivateSubscription
{
    public function handle(PaymentSucceeded $event): void
    {
        $payable = $event->payment->payable;

        if ($payable instanceof Subscription) {
            $payable->activate($event->payment->id);
        }
    }
}
