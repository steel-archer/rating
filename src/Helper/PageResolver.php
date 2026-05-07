<?php

declare(strict_types=1);

namespace App\Helper;

use Symfony\Component\HttpFoundation\Request;

final class PageResolver
{
    private const int MAX_PAGE = 10000;

    public static function resolve(Request $request): int
    {
        return min(max(1, $request->query->getInt('page', 1)), self::MAX_PAGE);
    }
}
