<?php

declare(strict_types=1);

namespace App\DTO\Response\My;

use DateTimeImmutable;

final readonly class SessionClaimListDTO
{
    public function __construct(
        public int $sessionId,
        public int $tournamentId,
        public string $tournamentName,
        public int $venueId,
        public string $venueName,
        public string $townName,
        public ?DateTimeImmutable $playedAt,
        public ?int $estimatedTeams,
        public string $status,
        public ?string $comment,
    ) {
    }
}
