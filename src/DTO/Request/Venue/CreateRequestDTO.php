<?php

namespace App\DTO\Request\Venue;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name = '',

        #[Assert\NotNull]
        #[Assert\Positive]
        public ?int $townId = null,
    ) {
    }
}
