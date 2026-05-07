<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PlayerListRequestDTO
{
    public function __construct(
        #[Assert\Range(min: 1, max: 10000)]
        public int $page = 1,

        #[Assert\Length(max: 255)]
        public ?string $lastName = null,

        #[Assert\Length(max: 255)]
        public ?string $firstName = null,

        #[Assert\Length(max: 255)]
        public ?string $patronymic = null,

        #[Assert\Positive]
        public ?int $townId = null,

        #[Assert\Positive]
        public ?int $countryId = null,
    ) {
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
