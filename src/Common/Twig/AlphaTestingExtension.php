<?php

declare(strict_types=1);

namespace App\Common\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AlphaTestingExtension extends AbstractExtension
{
    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_alpha_testing_mode', [AlphaTestingRuntime::class, 'isEnabled']),
        ];
    }
}
