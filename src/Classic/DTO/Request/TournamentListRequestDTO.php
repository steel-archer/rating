<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request;

use App\Classic\Enum\TournamentFormat;
use App\Classic\Enum\TournamentPeriod;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class TournamentListRequestDTO
{
    public function __construct(
        #[Assert\Range(min: 1, max: 10000)]
        public int $page = 1,

        #[Assert\Length(max: 255)]
        public ?string $name = null,

        public ?TournamentPeriod $period = null,

        public ?TournamentFormat $format = null,
    ) {
    }

    /**
     * @return array<string, string|int>
     */
    public function getFilters(): array
    {
        return array_filter([
            'name' => $this->name,
            'period' => $this->period?->value,
            'format' => $this->format?->value,
        ], static fn($v) => $v !== null && $v !== '');
    }
}
