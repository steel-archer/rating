<?php

declare(strict_types=1);

namespace App\DTO\Response\Tournament;

final readonly class OfficialDTO
{
    public function __construct(
        public int $playerId,
        public string $playerName,
        public string $role,
    ) {
    }
}
