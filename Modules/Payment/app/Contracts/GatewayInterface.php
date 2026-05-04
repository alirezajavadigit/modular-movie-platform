<?php

namespace Modules\Payment\Contracts;

use Modules\Payment\DTOs\CreatePaymentDTO;

interface GatewayInterface
{
    public function purchase(CreatePaymentDTO $paymentDTO): string;

    public function verify(string $transactionId): bool;
}
