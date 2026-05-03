<?php

namespace App\DTO\Response;

use App\DTO\Response\Team\SeasonSquadDTO;
use App\DTO\Response\Team\TournamentEntryDTO;

final readonly class TeamDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $townName,
        /** @var list<SeasonSquadDTO> */
        public array $squads = [],
        /** @var list<TournamentEntryDTO> */
        public array $tournaments = [],
    ) {
    }
}
