<?php

namespace App\DTO\Response\Player;

final readonly class SquadDTO
{
    public function __construct(
        public int $teamId,
        public string $teamName,
        public string $seasonName,
        public bool $isCaptain,
    ) {
    }
}
