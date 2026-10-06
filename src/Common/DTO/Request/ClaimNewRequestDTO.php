<?php

declare(strict_types=1);

namespace App\Common\DTO\Request;

use App\Common\Helper\NameNormalizer;
use App\Common\Validator\UkrainianName;
use App\Common\Validator\UkrainianTownName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Town and country are both optional, but a town (whether picked by id or typed
 * by name) always requires a country to be chosen alongside it.
 */
#[Assert\Expression(
    expression: '(this.townId === null and (this.townName === null or this.townName === "")) or this.countryId !== null',
    message: 'player_claim.country_required',
)]
final readonly class ClaimNewRequestDTO implements HasContactFields
{
    use ContactFieldsTrait;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[UkrainianName]
    public string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[UkrainianName]
    public string $lastName;

    #[Assert\Length(max: 255)]
    #[UkrainianName]
    public ?string $patronymic;

    public function __construct(
        string $firstName = '',
        string $lastName = '',
        ?string $patronymic = null,

        #[Assert\Positive]
        public ?int $townId = null,

        #[Assert\Length(max: 255)]
        #[UkrainianTownName]
        public ?string $townName = null,

        #[Assert\Positive]
        public ?int $countryId = null,

        #[Assert\IsTrue(message: 'player_claim.terms_required')]
        public bool $termsAccepted = false,

        ?string $telegram = null,
        ?string $facebook = null,
        ?string $phone = null,
    ) {
        $this->firstName = NameNormalizer::normalizeApostrophes($firstName) ?? '';
        $this->lastName = NameNormalizer::normalizeApostrophes($lastName) ?? '';
        $this->patronymic = NameNormalizer::normalizeApostrophes($patronymic);

        $this->telegram = $telegram;
        $this->facebook = $facebook;
        $this->phone = $phone;
    }
}
