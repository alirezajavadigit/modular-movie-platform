<?php

declare(strict_types=1);

namespace Modules\Subscription\Listeners;

use Modules\Payment\Events\PaymentFailed;
use Modules\Subscription\Models\Subscription;

final class FailSubscription
{
    public function handle(PaymentFailed $event): void
    {
        $payable = $event->payment->payable;

        if ($payable instanceof Subscription) {
            $payable->markPaymentFailed($event->payment->id);
        }
    }
}
