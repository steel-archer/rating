<?php

declare(strict_types=1);

namespace App\Common\DTO\Request;

use App\Common\Helper\NameNormalizer;
use App\Common\Validator\UkrainianName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PlayerListRequestDTO
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
        #[Assert\Range(min: 1, max: 10000)]
        public int $page = 1,
        ?string $lastName = null,
        ?string $firstName = null,
        ?string $patronymic = null,

        #[Assert\Positive]
        public ?int $townId = null,

        #[Assert\Positive]
        public ?int $countryId = null,
    ) {
        $this->lastName = NameNormalizer::normalizeApostrophes($lastName);
        $this->firstName = NameNormalizer::normalizeApostrophes($firstName);
        $this->patronymic = NameNormalizer::normalizeApostrophes($patronymic);
    }

    /**
     * @return array<string, string|int>
     */
    public function getFilters(): array
    {
        return array_filter([
            'lastName' => $this->lastName,
            'firstName' => $this->firstName,
            'patronymic' => $this->patronymic,
            'townId' => $this->townId,
            'countryId' => $this->countryId,
        ], static fn($v) => $v !== null && $v !== '');
    }
}
