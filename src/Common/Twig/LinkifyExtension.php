<?php

declare(strict_types=1);

namespace App\Common\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LinkifyExtension extends AbstractExtension
{
    /**
     * @return list<TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('linkify', [LinkifyRuntime::class, 'linkify'], ['is_safe' => ['html']]),
            new TwigFilter('safe_url', [LinkifyRuntime::class, 'safeUrl']),
        ];
    }
}
