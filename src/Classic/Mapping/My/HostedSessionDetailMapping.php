<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\HostedSessionDetailDTO;
use App\Classic\Entity\TournamentSession;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: TournamentSession::class, destination: HostedSessionDetailDTO::class)]
final class HostedSessionDetailMapping implements MappingInterface
{
    /**
     * @param TournamentSession $source
     * @return HostedSessionDetailDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $tournament = $source->getTournament();
        $venue = $source->getVenue();

        return new $destinationClass(
            sessionId: $source->getId(),
            tournamentId: $tournament->getId(),
            tournamentName: $tournament->getName(),
            venueName: $venue->getName(),
            townName: $venue->getTown()->getName(),
            playedAt: $source->getPlayedAt(),
            documents: $context['documents'] ?? [],
        );
    }
}
