<?php

declare(strict_types=1);

namespace App\DTO\Request\Session;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SquadRequestDTO
{
    public function __construct(
        #[Assert\Positive]
        public ?int $teamId = null,
        #[Assert\Length(max: 255)]
        public ?string $teamName = null,
        #[Assert\Positive]
        public ?int $townId = null,
        #[Assert\Length(max: 255)]
        public ?string $oneTimeName = null,
        /** @var list<SquadPlayerDTO> */
        #[Assert\Count(min: 1, max: 8)]
        #[Assert\Valid]
        public array $players = [],
        #[Assert\GreaterThanOrEqual(0)]
        public ?int $captainIndex = null,
    ) {
    }
}
