<?php

declare(strict_types=1);

namespace App\Common\Twig;

use App\Common\DTO\Response\ModerationCountsDTO;
use App\Common\DTO\Response\MyActionCountsDTO;
use App\Common\Entity\Player;
use App\Common\Service\MenuCounterService;
use Psr\Cache\InvalidArgumentException;
use Twig\Extension\RuntimeExtensionInterface;

class MenuCounterRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly MenuCounterService $menuCounterService)
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getModerationCounts(): ModerationCountsDTO
    {
        return $this->menuCounterService->getModerationCounts();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getMyActionCounts(Player $player): MyActionCountsDTO
    {
        return $this->menuCounterService->getMyActionCounts($player);
    }
}
