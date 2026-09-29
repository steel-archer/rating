<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Tournament;

use App\Classic\DTO\Response\Tournament\SessionDTO;
use App\Classic\DTO\Response\Tournament\SessionHostDTO;
use App\Classic\Entity\TournamentSession;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
            hostId: $host->getId(),
            hostName: $host->getFullName(),
            hostHasUser: $host->hasUser(),
            hosts: $this->buildHosts($source, $context),
            announcementUrl: $source->getAnnouncementUrl(),
            isOnline: $source->isOnline(),
        );
    }

    /**
     * Builds the full host list (current + former) for viewers allowed the
     * extended view. Data comes from the context (host history and download
     * flags gathered in batch by the controller), never queried in the mapper.
     * Returns an empty list when no host history was provided.
     *
     * @param array<string, mixed> $context
     * @return list<SessionHostDTO>
     */
    private function buildHosts(TournamentSession $source, array $context): array
    {
        /** @var array<int, list<array{playerId: int, playerName: string, hasUser: bool}>> $hostsBySession */
        $hostsBySession = $context['hostsBySession'] ?? [];
        $historyRows = $hostsBySession[$source->getId()] ?? [];

        if ($historyRows === []) {
            return [];
        }

        /** @var list<int> $downloadedPlayerIds */
        $downloadedPlayerIds = $context['downloadedPlayerIds'] ?? [];
        $downloadedLookup = array_fill_keys($downloadedPlayerIds, true);
        $currentHostId = $source->getHost()->getId();

        $hosts = array_map(
            static fn(array $row) => new SessionHostDTO(
                playerId: $row['playerId'],
                playerName: $row['playerName'],
                hasUser: $row['hasUser'],
                isCurrent: $row['playerId'] === $currentHostId,
                hasDownloaded: isset($downloadedLookup[$row['playerId']]),
            ),
            $historyRows,
        );

        /*
         * The current host is shown first; former hosts follow in the order they
         * were recorded (a stable sort keeps that relative order).
         */
        usort(
            $hosts,
            static fn(SessionHostDTO $a, SessionHostDTO $b) => ($b->isCurrent <=> $a->isCurrent),
        );

        return $hosts;
    }
}
