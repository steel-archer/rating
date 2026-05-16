<?php

declare(strict_types=1);

namespace App\DTO\Response\Tournament;

final readonly class SessionTeamPlayerSuggestDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $group,
        public bool $isCaptain = false,
    ) {
    }
}
