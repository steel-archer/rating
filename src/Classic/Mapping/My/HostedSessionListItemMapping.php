<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\HostedSessionListItemDTO;
use App\Classic\Entity\SessionClaim;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: SessionClaim::class, destination: HostedSessionListItemDTO::class)]
final class HostedSessionListItemMapping implements MappingInterface
{
    /**
     * @param SessionClaim $source
     * @return HostedSessionListItemDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $session = $source->getSession();
        $tournament = $session->getTournament();
        $venue = $session->getVenue();

        return new $destinationClass(
            sessionId: $session->getId(),
            tournamentId: $tournament->getId(),
            tournamentName: $tournament->getName(),
            venueName: $venue->getName(),
            townName: $venue->getTown()->getName(),
            playedAt: $session->getPlayedAt(),
            documentCount: $context['documentCounts'][$tournament->getId()] ?? 0,
        );
    }
}
