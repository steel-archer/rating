<?php

declare(strict_types=1);

namespace App\DTO\Request\Session;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SquadPlayerDTO
{
    public function __construct(
        #[Assert\Positive]
        public ?int $id = null,
        #[Assert\Length(max: 255)]
        public ?string $lastName = null,
        #[Assert\Length(max: 255)]
        public ?string $firstName = null,
        #[Assert\Length(max: 255)]
        public ?string $patronymic = null,
        #[Assert\Positive]
        public ?int $townId = null,
    ) {
    }
}
