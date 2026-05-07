<?php

declare(strict_types=1);

namespace App\DTO\Response\Tournament;

final readonly class TournamentContextDTO
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
