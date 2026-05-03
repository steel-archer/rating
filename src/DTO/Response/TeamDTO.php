<?php

namespace App\DTO\Response;

use App\DTO\Response\Team\SeasonSquadDTO;

final readonly class TeamDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $townName,
        public int $tournamentCount = 0,
        /** @var list<SeasonSquadDTO> */
        public array $squads = [],
    ) {
    }
}
