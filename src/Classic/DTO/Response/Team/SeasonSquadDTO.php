<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Team;

final readonly class SeasonSquadDTO
{
    public function __construct(
        public string $seasonName,
        /** @var list<SquadPlayerDTO> */
        public array $players = [],
    ) {
    }
}
