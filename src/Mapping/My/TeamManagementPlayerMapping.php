<?php

declare(strict_types=1);

namespace App\Mapping\My;

use App\DTO\Response\My\TeamManagementPlayerDTO;
use App\Entity\TeamPlayer;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TeamPlayer::class, destination: TeamManagementPlayerDTO::class)]
final class TeamManagementPlayerMapping implements MappingInterface
{
    /**
     * @param TeamPlayer $source
     * @return TeamManagementPlayerDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $player = $source->getPlayer();

        return new $destinationClass(
            playerId: $player->getId(),
            playerName: $player->getFullName(),
            isCaptain: $source->isCaptain(),
            hasUser: $player->hasUser(),
        );
    }
}
