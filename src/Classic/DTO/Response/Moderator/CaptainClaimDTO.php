<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Moderator;

use DateTimeImmutable;

final readonly class CaptainClaimDTO
{
    public function __construct(
        public int $id,
        public int $playerId,
        public string $playerName,
        public int $teamId,
        public string $teamName,
        public string $teamTownName,
        public string $comment,
        public DateTimeImmutable $createdAt,
        public bool $canApprove,
        public ?string $cannotApproveReason = null,
    ) {
    }
}
