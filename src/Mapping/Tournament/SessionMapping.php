<?php

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\SessionDTO;
use App\DTO\Response\Tournament\SessionTeamDTO;
use App\Entity\TournamentSession;
use App\Mapping\MappingInterface;

final class SessionMapping implements MappingInterface
{
    /**
     * @param array{teams: list<SessionTeamDTO>} $context
     * @return SessionDTO
     */
    public static function mapTo(mixed $source, string $destinationClass, array $context = []): object
    {
        /** @var TournamentSession $source */
        $venue = $source->getVenue();
        $representative = $source->getRepresentative();
        $host = $source->getHost();

        return new $destinationClass(
            venueId: $venue->getId(),
            venueName: $venue->getName(),
            townName: $venue->getTown()->getName(),
            representativeId: $representative->getId(),
            representativeName: $representative->getFullName(),
            hostId: $host?->getId(),
            hostName: $host?->getFullName(),
            playedAt: $source->getPlayedAt(),
            teams: $context['teams'] ?? [],
        );
    }
}
