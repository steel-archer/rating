<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Moderator;

use DateTimeImmutable;

final readonly class CaptainClaimResolvedDTO
{
    public function __construct(
        public int $id,
        public string $playerName,
        public int $teamId,
        public string $teamName,
        public string $teamTownName,
        public string $status,
        public string $comment,
        public ?string $moderatorComment,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $resolvedAt,
    ) {
    }
}
