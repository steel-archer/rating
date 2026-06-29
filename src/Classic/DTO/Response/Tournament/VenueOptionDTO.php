<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Tournament;

final readonly class VenueOptionDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $townName,
        public bool $isOnline,
    ) {
    }
}
