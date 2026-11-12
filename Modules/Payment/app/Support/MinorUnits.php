<?php

declare(strict_types=1);

namespace Modules\Payment\Support;

use InvalidArgumentException;

final class MinorUnits
{
    public static function fromDecimal(float|int|string $amount, int $scale = 2): int
    {
        $decimal = is_string($amount) ? trim($amount) : number_format((float) $amount, $scale, '.', '');

        if (!preg_match('/^(\d+)(?:\.(\d+))?$/', $decimal, $matches)) {
            throw new InvalidArgumentException("Invalid monetary amount [{$decimal}].");
        }

        $fraction = $matches[2] ?? '';

        if (strlen($fraction) > $scale) {
            throw new InvalidArgumentException("Monetary amount [{$decimal}] has more than {$scale} decimal places.");
        }

        return (int) ($matches[1] . str_pad($fraction, $scale, '0'));
    }
}
