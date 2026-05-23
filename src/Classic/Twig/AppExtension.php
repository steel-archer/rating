<?php

declare(strict_types=1);

namespace App\Classic\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_in_base_squad', [AppRuntime::class, 'isInBaseSquad']),
        ];
    }
}
