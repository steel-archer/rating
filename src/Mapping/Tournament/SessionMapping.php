<?php

declare(strict_types=1);

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\SessionDTO;
use App\Entity\TournamentSession;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TournamentSession::class, destination: SessionDTO::class)]
final class SessionMapping implements MappingInterface
{
    /**
     * @param TournamentSession $source
     * @return SessionDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $venue = $source->getVenue();
        $representative = $source->getRepresentative();
        $host = $source->getHost();

        return new $destinationClass(
            id: $source->getId(),
            venueId: $venue->getId(),
            venueName: $venue->getName(),
            townName: $venue->getTown()->getName(),
            playedAt: $source->getPlayedAt(),
            estimatedTeams: $source->getEstimatedTeams(),
            representativeId: $representative->getId(),
            representativeName: $representative->getFullName(),
            representativeHasUser: $representative->hasUser(),
            hostId: $host?->getId(),
            hostName: $host?->getFullName(),
            hostHasUser: $host?->hasUser() ?? false,
        );
    }
}
