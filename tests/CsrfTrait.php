<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Component\DomCrawler\Crawler;

trait CsrfTrait
{
    protected static function extractCsrfToken(Crawler $crawler, string $formAction): string
    {
        return $crawler
            ->filter('form[action*="' . $formAction . '"] input[name="_token"]')
            ->attr('value');
    }
}
