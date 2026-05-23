<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\TeamManagementPlayerDTO;
use App\Common\Entity\Player;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: Player::class, destination: TeamManagementPlayerDTO::class)]
final class TeamManagementPlayerFromPlayerMapping implements MappingInterface
{
    /**
     * @param Player $source
     * @return TeamManagementPlayerDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            playerId: $source->getId(),
            playerName: $source->getFullName(),
            isCaptain: false,
            hasUser: $source->hasUser(),
        );
    }
}
