<?php

declare(strict_types=1);

namespace App\DTO\Response\Team;

final readonly class TeamListItemDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $townName,
        public string $countryName,
    ) {
    }
}
