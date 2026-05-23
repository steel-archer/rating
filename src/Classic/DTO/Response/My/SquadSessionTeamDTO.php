<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\My;

final readonly class SquadSessionTeamDTO
{
    public function __construct(
        public int $id,
        public int $teamId,
        public string $teamName,
        public string $teamTownName,
        public ?string $oneTimeName,
    ) {
    }
}
