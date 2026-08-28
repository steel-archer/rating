<?php

declare(strict_types=1);

namespace App\Common\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MaintenanceExtension extends AbstractExtension
{
    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_maintenance_mode', [MaintenanceRuntime::class, 'isEnabled']),
        ];
    }
}
