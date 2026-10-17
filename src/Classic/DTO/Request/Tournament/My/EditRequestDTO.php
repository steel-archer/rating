<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\Tournament\My;

use App\Common\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class EditRequestDTO
{
    /**
     * @param list<OfficialPlayerDTO> $organizers
     * @param list<OfficialPlayerDTO> $editors
     * @param list<OfficialPlayerDTO> $gameJury
     * @param list<OfficialPlayerDTO> $appealJury
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

        public bool $customQuestionsPerTour = false,

        #[Assert\Positive]
        #[Assert\LessThanOrEqual(50)]
        public ?int $questionsPerTour = null,

        /** @var list<int>|null */
        #[Assert\Count(max: 50)]
        #[Assert\All([new Assert\Positive(), new Assert\LessThanOrEqual(50)])]
        public ?array $questionsPerTourMap = null,

        #[Assert\Range(min: 1, max: 10)]
        public ?float $difficulty = null,

        #[Assert\Length(max: 512)]
        #[Assert\Url(message: 'tournament.error.invalid_discussion_link', protocols: ['http', 'https'])]
        public ?string $discussionLink = null,

        /** @var list<OfficialPlayerDTO> */
        #[Assert\Count(max: 20)]
        #[Assert\Valid]
        public array $organizers = [],

        /** @var list<OfficialPlayerDTO> */
        #[Assert\Count(max: 20)]
        #[Assert\Valid]
        public array $editors = [],

        /** @var list<OfficialPlayerDTO> */
        #[Assert\Count(max: 20)]
        #[Assert\Valid]
        public array $gameJury = [],

        /** @var list<OfficialPlayerDTO> */
        #[Assert\Count(max: 20)]
        #[Assert\Valid]
        public array $appealJury = [],
    ) {
    }
}
