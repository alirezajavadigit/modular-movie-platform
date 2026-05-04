<?php

namespace Modules\Payment\DTOs;

use Modules\Payment\Contracts\PayableInterface;
use Modules\Payment\Enums\PaymentStatus;

readonly class CreatePaymentDTO
{
    public function __construct(
        public PayableInterface $payable,
        public int $userId,
        public string $driver,
        public PaymentStatus $status = PaymentStatus::PENDING
    ) {}
}
