<?php

namespace App\DTO\Response\Team;

final readonly class SquadPlayerDTO
{
    public function __construct(
        public int $playerId,
        public string $playerName,
        public bool $isCaptain,
    ) {
    }
}
