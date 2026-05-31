<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Common\Entity\Player;
use App\Classic\Entity\Tournament;
use App\Classic\Repository\TournamentOfficialRepository;

class TournamentDetailAccessService
{
    public function __construct(
        private TournamentOfficialRepository $officialRepository,
    ) {
    }

    public function canView(Tournament $tournament, ?Player $player): bool
    {
        if (!$tournament->areDetailsHidden()) {
            return true;
        }

        if ($player === null) {
            return false;
        }

        return $this->officialRepository->findOneBy([
            'tournament' => $tournament,
            'player' => $player,
        ]) !== null;
    }
}
