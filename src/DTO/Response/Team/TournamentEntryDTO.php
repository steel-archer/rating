<?php

namespace App\DTO\Response\Team;

use DateTimeInterface;

final readonly class TournamentEntryDTO
{
    public function __construct(
        public int $tournamentId,
        public string $tournamentName,
        public ?DateTimeInterface $playedAt,
        public ?int $score,
        public ?float $place,
        /** @var list<TournamentPlayerDTO> */
        public array $players = [],
    ) {
    }
}
