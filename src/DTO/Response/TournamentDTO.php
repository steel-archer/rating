<?php

namespace App\DTO\Response;

use App\DTO\Response\Tournament\OfficialDTO;
use DateTimeInterface;

final readonly class TournamentDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $seasonName,
        public ?DateTimeInterface $startedAt,
        public ?DateTimeInterface $endedAt,
        public ?int $toursCount,
        public ?int $questionsPerTour,
        public ?float $difficulty,
        public ?float $trueDl,
        public int $teamCount = 0,
        public int $sessionCount = 0,
        /** @var array<string, list<OfficialDTO>> role => officials */
        public array $officials = [],
    ) {
    }
}
