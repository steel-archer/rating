<?php

namespace App\DTO\Response;

use App\DTO\Response\Player\SquadDTO;
use App\DTO\Response\Player\TournamentAppearanceDTO;

final readonly class PlayerDTO
{
    public function __construct(
        public int $id,
        public string $fullName,
        public ?string $townName,
        /** @var list<SquadDTO> */
        public array $squads = [],
        /** @var list<TournamentAppearanceDTO> */
        public array $tournaments = [],
    ) {
    }
}
