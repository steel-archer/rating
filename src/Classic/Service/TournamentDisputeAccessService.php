<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Common\Entity\Player;
use App\Classic\Entity\Tournament;
use App\Classic\Repository\TournamentOfficialRepository;
use App\Classic\Repository\TournamentSessionRepository;
use App\Classic\Repository\TournamentSessionTeamPlayerRepository;

class TournamentDisputeAccessService
{
    public function __construct(
        private TournamentOfficialRepository $officialRepository,
        private TournamentSessionRepository $sessionRepository,
        private TournamentSessionTeamPlayerRepository $playerRepository,
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

        if ($this->officialRepository->findOneBy(['tournament' => $tournament, 'player' => $player]) !== null) {
            return true;
        }

        if ($this->sessionRepository->isRepresentativeOfTournament($player, $tournament)) {
            return true;
        }

        return $this->playerRepository->hasPlayedInTournament($player, $tournament);
    }
}
