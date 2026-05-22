<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Player;
use App\Service\TeamManagementService;
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
