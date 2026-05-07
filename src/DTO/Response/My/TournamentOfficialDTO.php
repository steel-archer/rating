<?php

declare(strict_types=1);

namespace App\DTO\Response\My;

final readonly class TournamentOfficialDTO
{
    public function __construct(
        public int $playerId,
        public string $playerName,
        public bool $hasUser,
        public string $role,
    ) {
    }
}
