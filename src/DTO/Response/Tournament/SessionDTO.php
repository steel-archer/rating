<?php

declare(strict_types=1);

namespace App\DTO\Response\Tournament;

use DateTimeImmutable;

final readonly class SessionDTO
{
    public function __construct(
        public int $id,
        public int $venueId,
        public string $venueName,
        public string $townName,
        public ?DateTimeImmutable $playedAt,
        public ?int $estimatedTeams,
        public int $representativeId,
        public string $representativeName,
        public bool $representativeHasUser,
        public ?int $hostId,
        public ?string $hostName,
        public bool $hostHasUser,
    ) {
    }
}
