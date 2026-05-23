<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\My;

use DateTimeImmutable;

final readonly class TournamentModerationListDTO
{
    public function __construct(
        public string $status,
        public ?string $comment,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $resolvedAt,
    ) {
    }
}
