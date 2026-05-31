<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\Tournament\My;

use App\Common\Validator\NoHtml;
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
        #[NoHtml]
        public string $name = '',

        #[Assert\Date]
        public ?string $startedAt = null,

        #[Assert\Date]
        public ?string $endedAt = null,

        #[Assert\Date]
        public ?string $resultsHiddenUntil = null,

        #[Assert\Date]
        public ?string $registrationDeadline = null,

        #[Assert\Date]
        public ?string $detailsHiddenUntil = null,

        #[Assert\Date]
        public ?string $submissionDeadline = null,

        #[Assert\Date]
        public ?string $appealDeadline = null,

        #[Assert\Positive]
        public ?int $toursCount = null,

        #[Assert\Positive]
        public ?int $questionsPerTour = null,

        #[Assert\Range(min: 1, max: 10)]
        public ?float $difficulty = null,

        /** @var list<int> */
        #[Assert\Count(max: 20)]
        #[Assert\All([new Assert\Positive()])]
        public array $organizers = [],

        /** @var list<int> */
        #[Assert\Count(max: 20)]
        #[Assert\All([new Assert\Positive()])]
        public array $editors = [],

        /** @var list<int> */
        #[Assert\Count(max: 20)]
        #[Assert\All([new Assert\Positive()])]
        public array $gameJury = [],

        /** @var list<int> */
        #[Assert\Count(max: 20)]
        #[Assert\All([new Assert\Positive()])]
        public array $appealJury = [],
    ) {
    }
}
