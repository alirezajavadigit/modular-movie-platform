<?php

namespace Modules\Payment\DTOs;

use Modules\Payment\Enums\PaymentStatus;

readonly class UpdatePaymentDTO
{
    public function __construct(
        public int $paymentId,
        public PaymentStatus $status,
        public ?string $transactionId = null
    ) {}
}
