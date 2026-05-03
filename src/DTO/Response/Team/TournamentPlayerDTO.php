<?php

namespace App\DTO\Response\Team;

final readonly class TournamentPlayerDTO
{
    public function __construct(
        public int $playerId,
        public string $playerName,
        public bool $isLegionary,
    ) {
    }
}
