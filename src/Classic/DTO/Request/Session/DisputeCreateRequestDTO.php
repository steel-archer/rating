<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\Session;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DisputeCreateRequestDTO
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public ?int $sessionTeamId = null,

        #[Assert\NotNull]
        #[Assert\Positive]
        public ?int $questionNumber = null,

        #[Assert\Length(max: 500)]
        public ?string $text = null,
    ) {
    }
}
