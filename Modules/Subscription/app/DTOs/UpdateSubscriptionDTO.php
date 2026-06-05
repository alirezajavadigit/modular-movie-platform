<?php

namespace Modules\Subscription\DTOs;

use Carbon\Carbon;
use Modules\Subscription\Enums\SubscriptionStatus;

readonly class UpdateSubscriptionDTO
{
    public function __construct(
        public ?SubscriptionStatus $status    = null,
        public ?Carbon             $startsAt  = null,
        public ?Carbon             $endsAt    = null,
        public ?int                $paymentId = null,
    ) {}
}
