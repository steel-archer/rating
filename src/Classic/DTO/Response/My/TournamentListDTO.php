<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\My;

use DateTimeImmutable;

final readonly class TournamentListDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $format,
        public string $status,
        public ?DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $endedAt,
    ) {
    }
}
