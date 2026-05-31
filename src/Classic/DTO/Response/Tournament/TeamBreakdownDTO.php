<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Tournament;

final readonly class TeamBreakdownDTO
{
    public function __construct(
        /** @var list<int|string> */
        public array $answers,
        /** @var list<int> */
        public array $tourScores,
    ) {
    }
}
