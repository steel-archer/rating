<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class VenueListRequestDTO
{
    public function __construct(
        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Length(max: 255)]
        public ?string $name = null,

        #[Assert\Positive]
        public ?int $townId = null,

        #[Assert\Positive]
        public ?int $countryId = null,

        #[Assert\Length(max: 255)]
        public ?string $representative = null,
    ) {
    }

    /**
     * @return array<string, string|int>
     */
    public function getFilters(): array
    {
        return array_filter([
            'name' => $this->name,
            'townId' => $this->townId,
            'countryId' => $this->countryId,
            'representative' => $this->representative,
        ], static fn($v) => $v !== null && $v !== '');
    }
}
