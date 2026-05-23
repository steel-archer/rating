<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\TeamManagement;

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
