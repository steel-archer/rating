<?php

namespace App\DTO\Response\Player;

use DateTimeInterface;

final readonly class TournamentAppearanceDTO
{
    public function __construct(
        public int $tournamentId,
        public string $tournamentName,
        public ?DateTimeInterface $playedAt,
        public int $teamId,
        public string $teamName,
        public ?int $score,
        public ?float $place,
        public bool $isLegionary,
    ) {
    }
}
