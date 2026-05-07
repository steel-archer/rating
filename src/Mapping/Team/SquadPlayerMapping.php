<?php

declare(strict_types=1);

namespace App\Mapping\Team;

use App\DTO\Response\Team\SquadPlayerDTO;
use App\Entity\TeamPlayer;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TeamPlayer::class, destination: SquadPlayerDTO::class)]
final class SquadPlayerMapping implements MappingInterface
{
    /**
     * @param TeamPlayer $source
     * @return SquadPlayerDTO
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
