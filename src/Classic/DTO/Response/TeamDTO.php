<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response;

use App\Classic\DTO\Response\Team\SeasonSquadDTO;

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
