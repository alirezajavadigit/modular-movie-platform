<?php

declare(strict_types=1);

namespace Modules\Person\Enums;

enum Gender: string
{
    case MALE         = 'male';
    case FEMALE       = 'female';
    case NON_BINARY   = 'non_binary';
    case UNDISCLOSED  = 'undisclosed';

    public static function values(): array
    {
        return array_map(fn(self $g) => $g->value, self::cases());
    }
}
