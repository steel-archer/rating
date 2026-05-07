<?php

declare(strict_types=1);

namespace App\DTO\Response\Venue;

use DateTimeImmutable;

final readonly class VenueTournamentDTO
{
    public function __construct(
        public int $tournamentId,
        public string $tournamentName,
        public ?DateTimeImmutable $playedAt,
    ) {
    }
}
