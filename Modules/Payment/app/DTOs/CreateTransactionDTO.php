<?php

namespace Modules\Payment\DTOs;

readonly class CreateTransactionDTO
{
    public function __construct(
        public int|string $paymentId,
        public string $transactionId,
        public string $status,
        public ?array $meta = null
    ) {}
}
