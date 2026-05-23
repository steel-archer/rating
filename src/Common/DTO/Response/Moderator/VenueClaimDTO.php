<?php

declare(strict_types=1);

namespace App\Common\DTO\Response\Moderator;

use DateTimeImmutable;

final readonly class VenueClaimDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $townName,
        public ?int $createdByPlayerId,
        public ?string $createdByPlayerName,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
