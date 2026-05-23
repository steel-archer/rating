<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Team;

use DateTimeInterface;

final readonly class TournamentEntryDTO
{
    public int $baseSquadCount;

    public function __construct(
        public int $tournamentId,
        public string $tournamentName,
        public ?DateTimeInterface $playedAt,
        public ?int $score,
        public ?float $place,
        /** @var list<TournamentPlayerDTO> */
        public array $players = [],
        public ?string $oneTimeName = null,
    ) {
        $this->baseSquadCount = count(array_filter($players, static fn(TournamentPlayerDTO $p) => $p->isBaseSquad));
    }
}
