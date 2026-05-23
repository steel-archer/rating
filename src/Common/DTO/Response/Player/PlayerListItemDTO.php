<?php

declare(strict_types=1);

namespace App\Common\DTO\Response\Player;

final readonly class PlayerListItemDTO
{
    public function __construct(
        public int $id,
        public string $fullName,
        public ?string $townName,
        public ?int $teamId,
        public ?string $teamName,
        public bool $hasUser,
    ) {
    }
}
