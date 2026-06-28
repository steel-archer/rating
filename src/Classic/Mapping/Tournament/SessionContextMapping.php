<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Tournament;

use App\Classic\DTO\Response\Tournament\SessionContextDTO;
use App\Classic\Entity\TournamentSession;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: TournamentSession::class, destination: SessionContextDTO::class)]
final class SessionContextMapping implements MappingInterface
{
    /**
     * @param TournamentSession $source
     * @return SessionContextDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $tournament = $source->getTournament();
        $venue = $source->getVenue();
        $representative = $source->getRepresentative();
        $host = $source->getHost();

        return new $destinationClass(
            id: $source->getId(),
            tournamentId: $tournament->getId(),
            tournamentName: $tournament->getName(),
            venueId: $venue->getId(),
            venueName: $venue->getName(),
            townName: $venue->getTown()->getName(),
            playedAt: $source->getPlayedAt(),
            representativeId: $representative->getId(),
            representativeName: $representative->getFullName(),
            representativeHasUser: $representative->hasUser(),
            hostId: $host?->getId(),
            hostName: $host?->getFullName(),
            hostHasUser: $host?->hasUser() ?? false,
            toursCount: $tournament->getToursCount() ?? 0,
            questionsPerTour: $tournament->getQuestionsPerTour() ?? 0,
        );
    }
}
