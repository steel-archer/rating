<?php

declare(strict_types=1);

namespace App\DTO\Response\Venue;

final readonly class VenueListItemDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $townName,
        public string $countryName,
    ) {
    }
}
