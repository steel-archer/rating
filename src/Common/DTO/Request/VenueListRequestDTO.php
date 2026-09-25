<?php

declare(strict_types=1);

namespace App\Common\DTO\Request;

use App\Common\Helper\NameNormalizer;
use App\Common\Validator\UkrainianName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class VenueListRequestDTO
{
    public const string MODE_ALL = 'all';
    public const string MODE_OFFLINE = 'offline';
    public const string MODE_ONLINE = 'online';

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

        #[Assert\Choice(choices: [self::MODE_ALL, self::MODE_OFFLINE, self::MODE_ONLINE])]
        public string $mode = self::MODE_ALL,

        ?string $representative = null,
    ) {
        $this->representative = NameNormalizer::normalizeApostrophes($representative);
    }

    /**
     * @return array<string, string|int>
     */
    public function getFilters(): array
    {
        // The "online only" mode ignores town/country filters, so they are
        // dropped from the propagated filter set in that case.
        $onlineOnly = $this->mode === self::MODE_ONLINE;

        return array_filter([
            'name' => $this->name,
            'townId' => $onlineOnly ? null : $this->townId,
            'countryId' => $onlineOnly ? null : $this->countryId,
            'mode' => $this->mode === self::MODE_ALL ? null : $this->mode,
            'representative' => $this->representative,
        ], static fn($v) => $v !== null && $v !== '');
    }
}
