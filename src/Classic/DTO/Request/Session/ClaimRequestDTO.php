<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\Session;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ClaimRequestDTO
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public ?int $venueId = null,

        #[Assert\Date]
        public ?string $playedAt = null,

        #[Assert\Positive]
        public ?int $estimatedTeams = null,

        #[Assert\Positive]
        public ?int $hostId = null,
    ) {
    }
}
