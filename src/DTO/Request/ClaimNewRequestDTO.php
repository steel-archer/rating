<?php

declare(strict_types=1);

namespace App\DTO\Request;

use App\Validator\UkrainianName;
use App\Validator\UkrainianTownName;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Expression(
    expression: 'this.townId !== null or (this.townName !== null and this.townName !== "")',
    message: 'player_claim.town_required',
)]
final readonly class ClaimNewRequestDTO implements HasContactFields
{
    use ContactFieldsTrait;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[UkrainianName]
        public string $firstName = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[UkrainianName]
        public string $lastName = '',

        #[Assert\Length(max: 255)]
        #[UkrainianName]
        public ?string $patronymic = null,

        #[Assert\Positive]
        public ?int $townId = null,

        #[Assert\Length(max: 255)]
        #[UkrainianTownName]
        public ?string $townName = null,

        ?string $telegram = null,
        ?string $facebook = null,
        ?string $phone = null,
    ) {
        $this->telegram = $telegram;
        $this->facebook = $facebook;
        $this->phone = $phone;
    }
}
