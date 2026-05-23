<?php

declare(strict_types=1);

namespace App\Classic\Enum;

enum AppealType: string
{
    case Accept = 'accept';
    case Remove = 'remove';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
