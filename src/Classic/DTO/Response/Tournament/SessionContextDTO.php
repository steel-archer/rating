<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Tournament;

use DateTimeImmutable;

final readonly class SessionContextDTO
{
    public function __construct(
        public int $id,
        public int $tournamentId,
        public string $tournamentName,
        public int $venueId,
        public string $venueName,
        public string $townName,
        public ?DateTimeImmutable $playedAt,
        public int $representativeId,
        public string $representativeName,
        public bool $representativeHasUser,
        public ?int $hostId,
        public ?string $hostName,
        public bool $hostHasUser,
        public int $toursCount,
        public int $questionsPerTour,
        public bool $isOnline = false,
    ) {
    }
}
