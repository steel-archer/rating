<?php

declare(strict_types=1);

namespace App\Mapping\My;

use App\DTO\Response\My\TeamManagementPlayerDTO;
use App\Entity\Player;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

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
