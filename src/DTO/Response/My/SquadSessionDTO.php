<?php

declare(strict_types=1);

namespace App\DTO\Response\My;

final readonly class SquadSessionDTO
{
    public function __construct(
        public int $id,
        public string $venueName,
    ) {
    }
}
