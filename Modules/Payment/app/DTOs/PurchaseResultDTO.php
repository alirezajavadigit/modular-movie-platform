<?php

namespace Modules\Payment\DTOs;

readonly class PurchaseResultDTO
{
    public function __construct(
        public string $redirectUrl,
        public string $reference,
    ) {}
}
