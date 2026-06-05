<?php

declare(strict_types=1);

namespace Modules\Person\Enums;

enum CreditRole: string
{
    case ACTOR          = 'actor';
    case DIRECTOR       = 'director';
    case WRITER         = 'writer';
    case PRODUCER       = 'producer';
    case EXECUTIVE      = 'executive_producer';
    case COMPOSER       = 'composer';
    case CINEMATOGRAPHER = 'cinematographer';
    case EDITOR         = 'editor';
    case CREW           = 'crew';
    case GUEST          = 'guest';
    case NARRATOR       = 'narrator';
    case OTHER          = 'other';

    public static function values(): array
    {
        return array_map(fn(self $c) => $c->value, self::cases());
    }

    public function isCast(): bool
    {
        return in_array($this, [self::ACTOR, self::GUEST, self::NARRATOR], true);
    }

    public function isCrew(): bool
    {
        return !$this->isCast();
    }
}
