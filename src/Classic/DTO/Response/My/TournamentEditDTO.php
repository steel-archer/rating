<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\My;

use DateTimeImmutable;

final readonly class TournamentEditDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $status,
        public string $format,
        public string $onlineMode,
        public ?DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $endedAt,
        public ?DateTimeImmutable $resultsHiddenUntil,
        public ?DateTimeImmutable $registrationDeadline,
        public ?DateTimeImmutable $detailsHiddenUntil,
        public ?DateTimeImmutable $submissionDeadline,
        public ?DateTimeImmutable $appealDeadline,
        public ?int $toursCount,
        public ?int $questionsPerTour,
        public ?float $difficulty,
        public ?string $discussionLink,
        public int $createdByPlayerId,
    ) {
    }
}
