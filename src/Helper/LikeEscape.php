<?php

declare(strict_types=1);

namespace App\Helper;

final class LikeEscape
{
    public static function contains(string $value): string
    {
        return '%' . self::escape($value) . '%';
    }

    private static function escape(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
