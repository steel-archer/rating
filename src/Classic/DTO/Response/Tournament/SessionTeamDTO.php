<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Tournament;

final readonly class SessionTeamDTO
{
    public int $baseSquadCount;

    public function __construct(
        public int $sessionTeamId,
        public int $teamId,
        public string $teamName,
        public ?string $teamTownName,
        public ?int $score,
        public ?int $maxScore,
        public ?float $place,
        /** @var list<SessionTeamPlayerDTO> */
        public array $players,
        public ?string $oneTimeName = null,
        public ?float $sessionPlace = null,
        public bool $isOnline = false,
    ) {
        $this->baseSquadCount = count(array_filter($players, static fn(SessionTeamPlayerDTO $player) => $player->isBaseSquad));
    }
}
