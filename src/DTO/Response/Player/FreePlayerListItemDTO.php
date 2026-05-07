<?php

declare(strict_types=1);

namespace App\DTO\Response\Player;

final readonly class FreePlayerListItemDTO
{
    public function __construct(
        public int $id,
        public string $fullName,
        public ?string $townName,
    ) {
    }
}
