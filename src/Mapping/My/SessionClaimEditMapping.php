<?php

declare(strict_types=1);

namespace App\Mapping\My;

use App\DTO\Response\My\SessionClaimEditDTO;
use App\Entity\SessionClaim;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: SessionClaim::class, destination: SessionClaimEditDTO::class)]
final class SessionClaimEditMapping implements MappingInterface
{
    /**
     * @param SessionClaim $source
     * @return SessionClaimEditDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $session = $source->getSession();
        $venue = $session->getVenue();
        $host = $session->getHost();

        return new $destinationClass(
            sessionId: $session->getId(),
            tournamentId: $session->getTournament()->getId(),
            tournamentName: $session->getTournament()->getName(),
            venueId: $venue->getId(),
            venueName: $venue->getName(),
            townName: $venue->getTown()->getName(),
            playedAt: $session->getPlayedAt(),
            estimatedTeams: $session->getEstimatedTeams(),
            hostId: $host?->getId(),
            hostName: $host?->getFullName(),
            hostHasUser: $host?->hasUser() ?? false,
            status: $source->getStatus()->value,
            comment: $source->getComment(),
        );
    }
}
