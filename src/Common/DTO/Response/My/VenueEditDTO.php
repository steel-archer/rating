<?php

declare(strict_types=1);

namespace App\Common\DTO\Response\My;

final readonly class VenueEditDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $townName,
        public int $createdByPlayerId,
        public bool $isApproved,
        public ?string $description = null,
        public ?string $url = null,
    ) {
    }
}
