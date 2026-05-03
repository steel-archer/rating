<?php

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\SessionDTO;
use App\DTO\Response\Tournament\SessionTeamDTO;
use App\Entity\TournamentSession;

final class SessionMapping
{
    /**
     * @param list<SessionTeamDTO> $teams
     */
    public static function toDTO(TournamentSession $session, array $teams): SessionDTO
    {
        $venue = $session->getVenue();

        return new SessionDTO(
            venueName: $venue->getName(),
            townName: $venue->getTown()->getName(),
            representativeName: $session->getRepresentative()->getFullName(),
            hostName: $session->getHost()?->getFullName(),
            playedAt: $session->getPlayedAt(),
            teams: $teams,
        );
    }
}
