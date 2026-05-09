<?php

namespace Modules\Subscription\DTOs;

readonly class CreateSubscriptionPlanDTO
{
    public function __construct(
        public string  $name,
        public float   $price,
        public int     $durationDays,
        public ?string $description = null,
    ) {}
}
