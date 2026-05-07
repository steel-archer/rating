<?php

declare(strict_types=1);

namespace App\DTO\Response\Moderator;

use DateTimeImmutable;

final readonly class TournamentClaimDTO
{
    public function __construct(
        public int $tournamentId,
        public string $tournamentName,
        public DateTimeImmutable $createdAt,
        public ?string $comment,
    ) {
    }
}
