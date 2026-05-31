<?php

declare(strict_types=1);

namespace App\Common\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CspExtension extends AbstractExtension
{
    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('csp_nonce', [CspRuntime::class, 'getNonce']),
        ];
    }
}
