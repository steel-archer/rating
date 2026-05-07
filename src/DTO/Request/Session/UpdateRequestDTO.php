<?php

declare(strict_types=1);

namespace App\DTO\Request\Session;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateRequestDTO
{
    public function __construct(
        #[Assert\Date]
        public ?string $playedAt = null,

        #[Assert\Positive]
        public ?int $estimatedTeams = null,

        #[Assert\Positive]
        public ?int $hostId = null,
    ) {
    }
}
