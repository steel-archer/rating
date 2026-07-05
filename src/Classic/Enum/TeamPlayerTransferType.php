<?php

declare(strict_types=1);

namespace App\Classic\Enum;

enum TeamPlayerTransferType: string
{
    case Joined = 'joined';
    case Left = 'left';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
