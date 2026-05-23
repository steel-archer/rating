<?php

declare(strict_types=1);

namespace App\Common\DTO\Response;

use App\Common\DTO\Response\Player\SquadDTO;

final readonly class PlayerDTO
{
    public function __construct(
        public int $id,
        public string $fullName,
        public ?string $townName,
        public bool $hasUser = false,
        public int $tournamentCount = 0,
        /** @var list<SquadDTO> */
        public array $squads = [],
    ) {
    }
}
