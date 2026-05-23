<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\Tournament\Moderation;

use App\Classic\Enum\TournamentModerationStatus;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ListRequestDTO
{
    public function __construct(
        #[Assert\Choice(callback: [TournamentModerationStatus::class, 'values'])]
        public string $status = 'pending',
    ) {
    }
}
