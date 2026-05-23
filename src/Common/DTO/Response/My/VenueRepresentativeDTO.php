<?php

declare(strict_types=1);

namespace App\Common\DTO\Response\My;

final readonly class VenueRepresentativeDTO
{
    public function __construct(
        public int $playerId,
        public string $playerName,
        public bool $hasUser,
    ) {
    }
}
