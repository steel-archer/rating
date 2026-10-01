<?php

declare(strict_types=1);

namespace App\Common\DTO\Response;

/**
 * Personal pending action counts for the current player
 * (organizer session claims, game jury disputes, appeal jury appeals).
 */
final readonly class MyActionCountsDTO
{
    public function __construct(
        public int $organizerClaims,
        public int $disputes,
        public int $appeals,
    ) {
    }

    public function getTotal(): int
    {
        return $this->organizerClaims + $this->disputes + $this->appeals;
    }
}
