<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Tournament;

final readonly class SessionTeamPlayerDTO
{
    public function __construct(
        public int $playerId,
        public string $playerName,
        public bool $isBaseSquad,
        public bool $isCaptain,
        public bool $hasUser = false,
    ) {
    }
}
