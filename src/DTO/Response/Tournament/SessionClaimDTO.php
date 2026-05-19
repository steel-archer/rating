<?php

declare(strict_types=1);

namespace App\DTO\Response\Tournament;

use App\DTO\Response\UserContactsDTO;
use DateTimeImmutable;

final readonly class SessionClaimDTO
{
    public function __construct(
        public int $sessionId,
        public int $venueId,
        public string $venueName,
        public string $townName,
        public ?DateTimeImmutable $playedAt,
        public ?int $estimatedTeams,
        public int $playerId,
        public string $playerName,
        public bool $playerHasUser,
        public UserContactsDTO $playerContacts,
        public ?int $hostId,
        public ?string $hostName,
        public bool $hostHasUser,
        public string $status,
        public ?string $comment,
    ) {
    }
}
