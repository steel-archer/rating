<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\Session;

use App\Common\Helper\NameNormalizer;
use App\Common\Validator\UkrainianName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SquadPlayerDTO
{
    #[Assert\Length(max: 255)]
    #[UkrainianName]
    public ?string $lastName;

    #[Assert\Length(max: 255)]
    #[UkrainianName]
    public ?string $firstName;

    #[Assert\Length(max: 255)]
    #[UkrainianName]
    public ?string $patronymic;

    public function __construct(
        #[Assert\Positive]
        public ?int $id = null,
        ?string $lastName = null,
        ?string $firstName = null,
        ?string $patronymic = null,
        #[Assert\Positive]
        public ?int $townId = null,
    ) {
        $this->lastName = NameNormalizer::normalizeApostrophes($lastName);
        $this->firstName = NameNormalizer::normalizeApostrophes($firstName);
        $this->patronymic = NameNormalizer::normalizeApostrophes($patronymic);
    }
}
