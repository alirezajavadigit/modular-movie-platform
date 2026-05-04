<?php

namespace Modules\Payment\Contracts;

interface PayableInterface
{
    public function getPayableId(): int|string;
    public function getPayableAmount(): float;
    public function getPayableDescription(): string;
}
