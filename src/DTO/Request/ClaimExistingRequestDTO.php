<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ClaimExistingRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $playerId = 0,
    ) {
    }
}
