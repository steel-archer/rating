<?php

declare(strict_types=1);

namespace App\Common\Twig;

use App\Common\Service\MaintenanceService;
use Psr\Cache\InvalidArgumentException;
use Twig\Extension\RuntimeExtensionInterface;

class MaintenanceRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly MaintenanceService $maintenanceService)
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function isEnabled(): bool
    {
        return $this->maintenanceService->isEnabled();
    }
}
