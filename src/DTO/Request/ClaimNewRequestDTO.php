<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ClaimNewRequestDTO
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $firstName = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $lastName = '',

        #[Assert\Length(max: 255)]
        public ?string $patronymic = null,

        #[Assert\Positive]
        public ?int $townId = null,
    ) {
    }
}
