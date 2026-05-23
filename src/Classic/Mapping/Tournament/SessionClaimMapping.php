<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Tournament;

use App\Classic\DTO\Response\Tournament\SessionClaimDTO;
use App\Common\DTO\Response\UserContactsDTO;
use App\Classic\Entity\SessionClaim;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: SessionClaim::class, destination: SessionClaimDTO::class)]
final class SessionClaimMapping implements MappingInterface
{
    /**
     * @param SessionClaim $source
     * @return SessionClaimDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $session = $source->getSession();
        $venue = $session->getVenue();
        $player = $source->getPlayer();
        $host = $session->getHost();

        return new $destinationClass(
            sessionId: $session->getId(),
            venueId: $venue->getId(),
            venueName: $venue->getName(),
            townName: $venue->getTown()->getName(),
            playedAt: $session->getPlayedAt(),
            estimatedTeams: $session->getEstimatedTeams(),
            playerId: $player->getId(),
            playerName: $player->getFullName(),
            playerHasUser: $player->hasUser(),
            playerContacts: $context['playerContacts'] ?? UserContactsDTO::empty(),
            hostId: $host?->getId(),
            hostName: $host?->getFullName(),
            hostHasUser: $host?->hasUser() ?? false,
            status: $source->getStatus()->value,
            comment: $source->getComment(),
        );
    }
}
