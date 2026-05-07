<?php

declare(strict_types=1);

namespace App\DTO\Request\Tournament\Moderation;

use App\Enum\TournamentModerationStatus;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ListRequestDTO
{
    public function __construct(
        #[Assert\Choice(callback: [TournamentModerationStatus::class, 'values'])]
        public string $status = 'pending',
    ) {
    }
}
