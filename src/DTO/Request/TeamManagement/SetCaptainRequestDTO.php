<?php

declare(strict_types=1);

namespace App\DTO\Request\TeamManagement;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SetCaptainRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $playerId,
    ) {
    }
}
