<?php

declare(strict_types=1);

namespace App\DTO\Response\Tournament;

final readonly class SessionTeamDTO
{
    public int $baseSquadCount;

    public function __construct(
        public int $teamId,
        public string $teamName,
        public ?string $teamTownName,
        public ?int $score,
        public ?float $place,
        /** @var list<SessionTeamPlayerDTO> */
        public array $players,
    ) {
        $this->baseSquadCount = count(array_filter($players, static fn(SessionTeamPlayerDTO $player) => $player->isBaseSquad));
    }
}
