<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Team;

use App\Classic\DTO\Response\Team\TournamentPlayerDTO;
use App\Classic\Entity\TournamentSessionTeamPlayer;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: TournamentSessionTeamPlayer::class, destination: TournamentPlayerDTO::class)]
final class TournamentPlayerMapping implements MappingInterface
{
    /**
     * @param TournamentSessionTeamPlayer $source
     * @return TournamentPlayerDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $player = $source->getPlayer();
        $playerId = $player->getId();
        $squadInfo = $context['squadInfo'] ?? ['playerIds' => [], 'captainId' => null];

        return new $destinationClass(
            playerId: $playerId,
            playerName: $player->getFullName(),
            isBaseSquad: in_array($playerId, $squadInfo['playerIds'], true),
            isCaptain: $playerId === $squadInfo['captainId'],
            isLegionary: $source->isLegionary(),
            hasUser: $player->hasUser(),
        );
    }
}
