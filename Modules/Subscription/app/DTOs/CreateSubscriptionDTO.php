<?php

namespace Modules\Subscription\DTOs;

readonly class CreateSubscriptionDTO
{
    public function __construct(
        public int    $userId,
        public int    $planId,
        public string $driver,
    ) {}
}
