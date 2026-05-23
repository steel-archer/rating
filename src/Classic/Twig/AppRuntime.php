<?php

declare(strict_types=1);

namespace App\Classic\Twig;

use App\Common\Entity\Player;
use App\Classic\Service\TeamManagementService;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Twig\Extension\RuntimeExtensionInterface;

class AppRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private TeamManagementService $teamManagementService,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     */
    public function isInBaseSquad(Player $player): bool
    {
        return $this->teamManagementService->isInBaseSquad($player);
    }
}
