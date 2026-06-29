<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\SessionClaimListDTO;
use App\Classic\Entity\SessionClaim;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: SessionClaim::class, destination: SessionClaimListDTO::class)]
final class SessionClaimListMapping implements MappingInterface
{
    /**
     * @param SessionClaim $source
     * @return SessionClaimListDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $session = $source->getSession();
        $venue = $session->getVenue();

        return new $destinationClass(
            sessionId: $session->getId(),
            tournamentId: $session->getTournament()->getId(),
            tournamentName: $session->getTournament()->getName(),
            venueId: $venue->getId(),
            venueName: $venue->getName(),
            townName: $venue->getTown()->getName(),
            playedAt: $session->getPlayedAt(),
            estimatedTeams: $session->getEstimatedTeams(),
            status: $source->getStatus()->value,
            comment: $source->getComment(),
            isOnline: $session->isOnline(),
        );
    }
}
