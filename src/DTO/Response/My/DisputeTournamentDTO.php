<?php

declare(strict_types=1);

namespace App\DTO\Response\My;

final readonly class DisputeTournamentDTO
{
    public function __construct(
        public int $tournamentId,
        public string $tournamentName,
        public int $total,
        public int $resolved,
    ) {
    }
}
