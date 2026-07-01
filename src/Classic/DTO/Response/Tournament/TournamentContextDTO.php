<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Tournament;

use DateTimeImmutable;

final readonly class TournamentContextDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $format = 'distributed',
        public string $onlineMode = 'mixed',
        public ?DateTimeImmutable $startedAt = null,
        public ?DateTimeImmutable $endedAt = null,
    ) {
    }
}
