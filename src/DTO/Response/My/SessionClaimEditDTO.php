<?php

declare(strict_types=1);

namespace App\DTO\Response\My;

use DateTimeImmutable;

final readonly class SessionClaimEditDTO
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
        public ?int $hostId,
        public ?string $hostName,
        public bool $hostHasUser,
        public string $status,
        public ?string $comment,
    ) {
    }
}
