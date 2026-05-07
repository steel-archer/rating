<?php

declare(strict_types=1);

namespace App\DTO\Response\My;

use DateTimeImmutable;

final readonly class TournamentEditDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $status,
        public ?DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $endedAt,
        public ?int $toursCount,
        public ?int $questionsPerTour,
        public ?float $difficulty,
        public int $createdByPlayerId,
    ) {
    }
}
