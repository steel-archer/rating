<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TournamentListRequestDTO
{
    public function __construct(
        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Length(max: 255)]
        public ?string $name = null,
    ) {
    }

    /**
     * @return array<string, string|int>
     */
    public function getFilters(): array
    {
        return array_filter([
            'name' => $this->name,
        ], static fn($v) => $v !== null && $v !== '');
    }
}
