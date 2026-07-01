<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Venue;

use DateTimeImmutable;

final readonly class VenueTournamentDTO
{
    public function __construct(
        public int $tournamentId,
        public int $sessionId,
        public string $tournamentName,
        public string $tournamentFormat,
        public string $tournamentOnlineMode,
        public ?DateTimeImmutable $playedAt,
        public int $teamsCount,
    ) {
    }
}
