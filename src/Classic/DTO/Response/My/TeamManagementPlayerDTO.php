<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\My;

final readonly class TeamManagementPlayerDTO
{
    public function __construct(
        public int $playerId,
        public string $playerName,
        public bool $isCaptain,
        public bool $hasUser,
    ) {
    }
}
