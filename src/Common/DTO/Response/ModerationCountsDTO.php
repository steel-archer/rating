<?php

declare(strict_types=1);

namespace App\Common\DTO\Response;

/**
 * Pending moderation action counts shared across all moderators.
 */
final readonly class ModerationCountsDTO
{
    public function __construct(
        public int $playerClaims,
        public int $tournaments,
        public int $captainClaims,
        public int $venues,
    ) {
    }

    public function getTotal(): int
    {
        return $this->playerClaims + $this->tournaments + $this->captainClaims + $this->venues;
    }
}
