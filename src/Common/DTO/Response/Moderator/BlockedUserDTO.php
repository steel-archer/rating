<?php

declare(strict_types=1);

namespace App\Common\DTO\Response\Moderator;

final readonly class BlockedUserDTO
{
    public function __construct(
        public int $id,
        public string $email,
        public ?int $playerId,
        public ?string $playerName,
        public bool $isBlocked,
        public ?string $blockedReason,
    ) {
    }
}
