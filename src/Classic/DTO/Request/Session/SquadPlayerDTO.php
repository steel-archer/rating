<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\Session;

use App\Common\Helper\NameNormalizer;
use App\Common\Validator\UkrainianName;
use App\Common\Validator\UkrainianTownName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A town (picked by id or typed by name) always requires a country alongside it;
 * both stay optional when no town is given.
 */
#[Assert\Expression(
    expression: '(this.townId === null and (this.townName === null or this.townName === "")) or this.countryId !== null',
    message: 'squad.error.country_required',
)]
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
        #[Assert\Length(max: 255)]
        #[UkrainianTownName]
        public ?string $townName = null,
        #[Assert\Positive]
        public ?int $countryId = null,
    ) {
        $this->lastName = NameNormalizer::normalizeApostrophes($lastName);
        $this->firstName = NameNormalizer::normalizeApostrophes($firstName);
        $this->patronymic = NameNormalizer::normalizeApostrophes($patronymic);
    }
}
