<?php

declare(strict_types=1);

namespace App\DTO\Response\My;

final readonly class TeamManagementDTO
{
    public function __construct(
        public int $teamId,
        public string $teamName,
        public int $townId,
        public string $townName,
        public string $seasonName,
        public bool $isCaptain,
        /** @var list<TeamManagementPlayerDTO> */
        public array $players,
        /** @var list<TeamManagementPlayerDTO> */
        public array $seasonPlayers = [],
        /** @var list<TeamManagementPlayerDTO> */
        public array $previousSeasonPlayers = [],
    ) {
    }
}
