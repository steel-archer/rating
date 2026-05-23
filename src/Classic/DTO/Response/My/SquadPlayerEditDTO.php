<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\My;

final readonly class SquadPlayerEditDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isCaptain,
    ) {
    }
}
