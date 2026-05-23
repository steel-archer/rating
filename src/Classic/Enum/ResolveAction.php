<?php

declare(strict_types=1);

namespace App\Classic\Enum;

enum ResolveAction: string
{
    case Accept = 'accept';
    case Reject = 'reject';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
