<?php

namespace Modules\Payment\Contracts;

use Modules\Payment\DTOs\CreatePaymentDTO;
use Modules\Payment\DTOs\PurchaseResultDTO;

interface GatewayInterface
{
    public function purchase(CreatePaymentDTO $paymentDTO): PurchaseResultDTO;

    public function verify(string $transactionId, float $amount = 0.0): bool;
}
