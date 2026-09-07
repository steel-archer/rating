<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\My;

use DateTimeImmutable;

final readonly class HostedSessionDetailDTO
{
    /**
     * @param list<TournamentDocumentDTO> $documents
     */
    public function __construct(
        public int $sessionId,
        public int $tournamentId,
        public string $tournamentName,
        public string $venueName,
        public string $townName,
        public ?DateTimeImmutable $playedAt,
        public array $documents,
    ) {
    }
}
