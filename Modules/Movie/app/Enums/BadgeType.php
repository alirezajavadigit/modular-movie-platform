<?php

namespace Modules\Movie\Enums;

enum BadgeType: string
{
    case Dubbed = 'dubbed';
    case Subtitled = 'subtitled';
    case Animation = 'animation';
}
