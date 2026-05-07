<?php

declare(strict_types=1);

namespace App\DTO\Response\My;

final readonly class VenueListDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $townName,
        public bool $isApproved,
    ) {
    }
}
