<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Team;

final readonly class TournamentPlayerDTO
{
    public function __construct(
        public int $playerId,
        public string $playerName,
        public bool $isBaseSquad,
        public bool $isCaptain,
        public bool $isLegionary,
        public bool $hasUser = false,
    ) {
    }
}
