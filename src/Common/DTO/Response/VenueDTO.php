<?php

declare(strict_types=1);

namespace App\Common\DTO\Response;

use App\Common\DTO\Response\Venue\RepresentativeDTO;

final readonly class VenueDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $townName,
        public int $tournamentCount = 0,
        /** @var list<RepresentativeDTO> */
        public array $representatives = [],
    ) {
    }
}
