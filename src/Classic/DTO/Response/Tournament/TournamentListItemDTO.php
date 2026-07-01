<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Tournament;

use DateTimeImmutable;

final readonly class TournamentListItemDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $format,
        public ?DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $endedAt,
        public ?float $difficulty,
        public ?float $trueDl,
        public int $teamCount,
    ) {
    }
}
