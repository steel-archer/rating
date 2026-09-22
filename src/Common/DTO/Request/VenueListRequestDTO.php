<?php

declare(strict_types=1);

namespace App\Common\DTO\Request;

use App\Common\Helper\NameNormalizer;
use App\Common\Validator\UkrainianName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class VenueListRequestDTO
{
    #[Assert\Length(max: 255)]
    #[UkrainianName]
    public ?string $representative;

    public function __construct(
        #[Assert\Range(min: 1, max: 10000)]
        public int $page = 1,

        #[Assert\Length(max: 255)]
        public ?string $name = null,

        #[Assert\Positive]
        public ?int $townId = null,

        #[Assert\Positive]
        public ?int $countryId = null,

        ?string $representative = null,
    ) {
        $this->representative = NameNormalizer::normalizeApostrophes($representative);
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
