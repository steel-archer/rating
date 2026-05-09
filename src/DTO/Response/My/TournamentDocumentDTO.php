<?php

declare(strict_types=1);

namespace App\DTO\Response\My;

use DateTimeImmutable;

final readonly class TournamentDocumentDTO
{
    public function __construct(
        public int $id,
        public string $originalName,
        public int $size,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
