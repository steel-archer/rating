<?php

namespace App\DTO\Response;

use App\Entity\VenueRepresentative;

final readonly class VenueDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $townName,
        public int $tournamentCount = 0,
        /** @var list<VenueRepresentative> */
        public array $representatives = [],
    ) {
    }
}
