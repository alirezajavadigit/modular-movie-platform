<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Unit;

use InvalidArgumentException;
use Modules\Payment\Support\MinorUnits;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MinorUnitsTest extends TestCase
{
    public static function amounts(): array
    {
        return [
            'float with cents'          => [19.99, 1999],
            'float that drifts below'   => [0.29, 29],
            'float sum'                 => [0.1 + 0.2, 30],
            'whole float'               => [500.0, 50000],
            'integer'                   => [5, 500],
            'decimal string from model' => ['19.99', 1999],
            'string with one decimal'   => ['10.5', 1050],
            'string without decimals'   => ['42', 4200],
            'large amount'              => ['1234567890123.45', 123456789012345],
            'zero'                      => ['0.00', 0],
        ];
    }

    #[DataProvider('amounts')]
    public function test_converts_decimal_amounts_to_minor_units(float|int|string $amount, int $expected): void
    {
        $this->assertSame($expected, MinorUnits::fromDecimal($amount));
    }

    public function test_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MinorUnits::fromDecimal('-1.00');
    }

    public function test_rejects_strings_with_more_precision_than_the_currency_supports(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MinorUnits::fromDecimal('19.999');
    }

    public function test_rejects_non_numeric_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MinorUnits::fromDecimal('abc');
    }
}
