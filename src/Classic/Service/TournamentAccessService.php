<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Classic\Entity\Tournament;
use App\Classic\Enum\TournamentStatus;
use App\Classic\Repository\TournamentOfficialRepository;
use App\Classic\Repository\TournamentSessionRepository;
use App\Classic\Repository\TournamentSessionTeamPlayerRepository;
use App\Common\Entity\Player;

/**
 * Central authority for who may see a tournament and its results. Groups the
 * three related access questions so the "official person" check lives in one
 * place and the pages stay consistent:
 *
 * - isVisible: the tournament exists for this viewer at all (publication);
 * - canViewDetails: per-question breakdown behind detailsHiddenUntil;
 * - canViewDisputes: disputes and appeals behind detailsHiddenUntil.
 */
class TournamentAccessService
{
    public function __construct(
        private TournamentOfficialRepository $officialRepository,
        private TournamentSessionRepository $sessionRepository,
        private TournamentSessionTeamPlayerRepository $playerRepository,
    ) {
    }

    /**
     * Unpublished tournaments (drafts) are only visible to their organizers
     * (creator and co-organizers alike) and moderators. Keeps the tournament
     * card, results and detailed-results pages consistent.
     */
    public function isVisible(Tournament $tournament, ?Player $player, bool $isModerator): bool
    {
        if ($tournament->getStatus() === TournamentStatus::Published) {
            return true;
        }

        if ($isModerator) {
            return true;
        }

        return $player !== null && $this->officialRepository->isOrganizer($player, $tournament);
    }

    /**
     * Per-question breakdown is open to everyone once details are no longer
     * hidden; while hidden, only tournament officials may see it.
     */
    public function canViewDetails(Tournament $tournament, ?Player $player): bool
    {
        if (!$tournament->areDetailsHidden()) {
            return true;
        }

        return $player !== null && $this->isOfficial($tournament, $player);
    }

    /**
     * Disputes and appeals follow the detail-visibility rule, but while hidden
     * they are also open to venue representatives of the tournament and to
     * players who took part in it.
     */
    public function canViewDisputes(Tournament $tournament, ?Player $player): bool
    {
        if (!$tournament->areDetailsHidden()) {
            return true;
        }

        if ($player === null) {
            return false;
        }

        if ($this->isOfficial($tournament, $player)) {
            return true;
        }

        if ($this->sessionRepository->isRepresentativeOfTournament($player, $tournament)) {
            return true;
        }

        return $this->playerRepository->hasPlayedInTournament($player, $tournament);
    }

    private function isOfficial(Tournament $tournament, Player $player): bool
    {
        return $this->officialRepository->findOneBy([
            'tournament' => $tournament,
            'player' => $player,
        ]) !== null;
    }
}
