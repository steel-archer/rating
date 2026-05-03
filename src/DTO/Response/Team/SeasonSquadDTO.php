<?php

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
