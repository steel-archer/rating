<?php

namespace App\DTO\Response\Tournament;

use DateTimeInterface;

final readonly class SessionDTO
{
    public function __construct(
        public string $venueName,
        public string $townName,
        public string $representativeName,
        public ?string $hostName,
        public ?DateTimeInterface $playedAt,
        /** @var list<SessionTeamDTO> */
        public array $teams,
    ) {
    }
}
