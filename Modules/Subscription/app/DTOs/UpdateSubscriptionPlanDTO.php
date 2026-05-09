<?php

namespace Modules\Subscription\DTOs;

use Modules\Subscription\Enums\SubscriptionPlanStatus;

readonly class UpdateSubscriptionPlanDTO
{
    public function __construct(
        public ?string                  $name         = null,
        public ?string                  $description  = null,
        public ?float                   $price        = null,
        public ?int                     $durationDays = null,
        public ?SubscriptionPlanStatus  $status       = null,
    ) {}
}
