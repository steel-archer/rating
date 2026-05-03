<?php

namespace App\DTO\Response;

use App\DTO\Response\Player\SquadDTO;

final readonly class PlayerDTO
{
    public function __construct(
        public int $id,
        public string $fullName,
        public ?string $townName,
        public int $tournamentCount = 0,
        /** @var list<SquadDTO> */
        public array $squads = [],
    ) {
    }
}
