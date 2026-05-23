<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Team;

use App\Classic\DTO\Response\Team\SquadPlayerDTO;
use App\Classic\Entity\TeamPlayer;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
