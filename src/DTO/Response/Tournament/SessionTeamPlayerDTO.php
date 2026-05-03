<?php

namespace App\DTO\Response\Tournament;

final readonly class SessionTeamPlayerDTO
{
    public function __construct(
        public int $playerId,
        public string $playerName,
        public bool $isBaseSquad,
        public bool $isCaptain,
    ) {
    }
}
