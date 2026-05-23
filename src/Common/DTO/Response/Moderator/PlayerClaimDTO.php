<?php

declare(strict_types=1);

namespace App\Common\DTO\Response\Moderator;

final readonly class PlayerClaimDTO
{
    public function __construct(
        public int $id,
        public string $userEmail,
        public bool $isNew,
        public ?int $playerId,
        public ?string $playerName,
        public bool $playerHasUser,
        public ?string $lastName,
        public ?string $firstName,
        public ?string $patronymic,
        public ?string $townName,
        public ?int $townId,
        public bool $townIsNew,
    ) {
    }
}
