<?php

declare(strict_types=1);

namespace App\Common\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MenuCounterExtension extends AbstractExtension
{
    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('moderation_counts', [MenuCounterRuntime::class, 'getModerationCounts']),
            new TwigFunction('my_action_counts', [MenuCounterRuntime::class, 'getMyActionCounts']),
        ];
    }
}
