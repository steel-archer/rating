<?php

declare(strict_types=1);

namespace App\DTO\Response\Venue;

final readonly class RepresentativeDTO
{
    public function __construct(
        public int $playerId,
        public string $playerName,
        public bool $hasUser = false,
    ) {
    }
}
