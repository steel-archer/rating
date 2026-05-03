<?php

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\SessionTeamDTO;
use App\DTO\Response\Tournament\SessionTeamPlayerDTO;
use App\Entity\TournamentSessionTeam;
use App\Mapping\MappingInterface;

final class SessionTeamMapping implements MappingInterface
{
    /**
     * @param array{venueId: int, venueName: string, townName: string, players: list<SessionTeamPlayerDTO>} $context
     * @return SessionTeamDTO
     */
    public static function mapTo(mixed $source, string $destinationClass, array $context = []): object
    {
        /** @var TournamentSessionTeam $source */
        $team = $source->getTeam();

        return new $destinationClass(
            teamId: $team->getId(),
            teamName: $team->getName(),
            score: $source->getScore(),
            venueId: $context['venueId'],
            venueName: $context['venueName'],
            townName: $context['townName'],
            players: $context['players'] ?? [],
        );
    }
}
