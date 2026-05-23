<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TeamListRequestDTO
{
    public function __construct(
        #[Assert\Range(min: 1, max: 10000)]
        public int $page = 1,

        #[Assert\Length(max: 255)]
        public ?string $name = null,

        #[Assert\Positive]
        public ?int $townId = null,

        #[Assert\Positive]
        public ?int $countryId = null,

        #[Assert\Choice(choices: ['name', 'town'])]
        public string $sort = 'name',

        #[Assert\Choice(choices: ['asc', 'desc'])]
        public string $dir = 'asc',
    ) {
    }

    /**
     * @return array<string, string|int>
     */
    public function getFilters(): array
    {
        $filters = array_filter([
            'name' => $this->name,
            'townId' => $this->townId,
            'countryId' => $this->countryId,
        ], static fn($v) => $v !== null && $v !== '');

        if ($this->sort !== 'name' || $this->dir !== 'asc') {
            $filters['sort'] = $this->sort;
            $filters['dir'] = $this->dir;
        }

        return $filters;
    }

    public function toggleDir(): string
    {
        return $this->dir === 'asc' ? 'desc' : 'asc';
    }
}
