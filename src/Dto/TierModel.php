<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

enum TierModel: string
{
    case REGULAR = 'REGULAR';
    case PREMIUM = 'PREMIUM';
    case UNKNOWN = 'UNKNOWN';
}
