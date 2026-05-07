<?php

declare(strict_types=1);

namespace App\DTO\Response\Team;

final readonly class SeasonSquadDTO
{
    public function __construct(
        public string $seasonName,
        /** @var list<SquadPlayerDTO> */
        public array $players = [],
    ) {
    }
}
