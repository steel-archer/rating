<?php

namespace App\DTO\Response\Tournament;

final readonly class SessionTeamPlayerDTO
{
    public function __construct(
        public int $playerId,
        public string $playerName,
        public bool $isLegionary,
        public bool $isCaptain,
        public bool $isBaseSquad,
    ) {
    }
}
