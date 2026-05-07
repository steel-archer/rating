<?php

namespace App\DTO\Response\Venue;

final readonly class RepresentativeDTO
{
    public function __construct(
        public int $playerId,
        public string $playerName,
    ) {
    }
}
