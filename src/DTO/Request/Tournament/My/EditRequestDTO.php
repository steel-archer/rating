<?php

namespace App\DTO\Request\Tournament\My;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class EditRequestDTO
{
    /**
     * @param list<int> $organizers
     * @param list<int> $editors
     * @param list<int> $gameJury
     * @param list<int> $appealJury
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name = '',

        public ?string $startedAt = null,

        public ?string $endedAt = null,

        #[Assert\Positive]
        public ?int $toursCount = null,

        #[Assert\Positive]
        public ?int $questionsPerTour = null,

        #[Assert\Range(min: 1, max: 10)]
        public ?float $difficulty = null,

        /** @var list<int> */
        public array $organizers = [],

        /** @var list<int> */
        public array $editors = [],

        /** @var list<int> */
        public array $gameJury = [],

        /** @var list<int> */
        public array $appealJury = [],
    ) {
    }
}
