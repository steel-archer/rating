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
        $player = $source->getPlayer();
        $playerId = $player->getId();
        $squadInfo = $context['squadInfo'] ?? ['playerIds' => [], 'captainId' => null];

        return new $destinationClass(
            playerId: $playerId,
            playerName: $player->getFullName(),
            isBaseSquad: in_array($playerId, $squadInfo['playerIds'], true),
            isCaptain: $playerId === $squadInfo['captainId'],
            isLegionary: $source->isLegionary(),
        );
    }
}
