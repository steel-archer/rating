<?php

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\SessionTeamDTO;
use App\DTO\Response\Tournament\SessionTeamPlayerDTO;
use App\Entity\TournamentSessionTeam;

final class SessionTeamMapping
{
    /**
     * @param list<SessionTeamPlayerDTO> $players
     */
    public static function toDTO(TournamentSessionTeam $st, string $venueName, string $townName, array $players): SessionTeamDTO
    {
        $team = $st->getTeam();

        return new SessionTeamDTO(
            teamId: $team->getId(),
            teamName: $team->getName(),
            score: $st->getScore(),
            venueName: $venueName,
            townName: $townName,
            players: $players,
        );
    }
}
