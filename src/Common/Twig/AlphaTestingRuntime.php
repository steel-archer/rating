<?php

declare(strict_types=1);

namespace App\Common\Twig;

use App\Common\Service\AlphaTestingService;
use Twig\Extension\RuntimeExtensionInterface;

class AlphaTestingRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly AlphaTestingService $alphaTestingService)
    {
    }

    public function isEnabled(): bool
    {
        return $this->alphaTestingService->isEnabled();
    }
}
