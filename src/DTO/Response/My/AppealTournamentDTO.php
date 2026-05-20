<?php

declare(strict_types=1);

namespace App\DTO\Response\My;

final readonly class AppealTournamentDTO
{
    public function __construct(
        public int $tournamentId,
        public string $tournamentName,
        public int $total,
        public int $resolved,
    ) {
    }
}
