<?php

namespace App\DTO\Response\Tournament;

use DateTimeInterface;

final readonly class SessionDTO
{
    public function __construct(
        public int $venueId,
        public string $venueName,
        public string $townName,
        public int $representativeId,
        public string $representativeName,
        public ?int $hostId,
        public ?string $hostName,
        public ?DateTimeInterface $playedAt,
        /** @var list<SessionTeamDTO> */
        public array $teams,
    ) {
    }
}
