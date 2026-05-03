<?php

namespace App\Mapping\Team;

use App\DTO\Response\Team\TournamentPlayerDTO;
use App\Entity\TournamentSessionTeamPlayer;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TournamentSessionTeamPlayer::class, destination: TournamentPlayerDTO::class)]
final class TournamentPlayerMapping implements MappingInterface
{
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        /** @var TournamentSessionTeamPlayer $source */
        return new $destinationClass(
            playerId: $source->getPlayer()->getId(),
            playerName: $source->getPlayer()->getFullName(),
            isLegionary: $source->isLegionary(),
        );
    }
}
