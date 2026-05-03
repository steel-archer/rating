<?php

namespace App\DTO\Response\Tournament;

final readonly class SessionTeamDTO
{
    public int $baseSquadCount;

    public function __construct(
        public int $teamId,
        public string $teamName,
        public ?int $score,
        public string $venueName,
        public string $townName,
        /** @var list<SessionTeamPlayerDTO> */
        public array $players,
    ) {
        $this->baseSquadCount = count(array_filter($players, static fn(SessionTeamPlayerDTO $p) => $p->isBaseSquad));
    }
}
