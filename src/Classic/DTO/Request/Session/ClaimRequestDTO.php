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

        #[Assert\NotNull(message: 'session_claim.error.host_required')]
        #[Assert\Positive(message: 'session_claim.error.host_required')]
        public ?int $hostId = null,

        public bool $isOnline = false,

        #[Assert\Length(max: 255)]
        #[Assert\Url(message: 'session_claim.error.invalid_announcement_url', protocols: ['http', 'https'])]
        public ?string $announcementUrl = null,
    ) {
    }
}
