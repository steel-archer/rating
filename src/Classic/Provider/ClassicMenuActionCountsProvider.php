<?php

declare(strict_types=1);

namespace App\Classic\Provider;

use App\Classic\Enum\TournamentOfficialRole;
use App\Classic\Repository\AppealRepository;
use App\Classic\Repository\CaptainClaimRepository;
use App\Classic\Repository\SessionClaimRepository;
use App\Classic\Repository\TournamentModerationClaimRepository;
use App\Classic\Repository\TournamentOfficialRepository;
use App\Classic\Repository\TournamentSessionTeamAnswerRepository;
use App\Common\Contract\MenuActionCountsProviderInterface;
use App\Common\Entity\Player;

final readonly class ClassicMenuActionCountsProvider implements MenuActionCountsProviderInterface
{
    public function __construct(
        private TournamentModerationClaimRepository $tournamentModerationClaimRepository,
        private CaptainClaimRepository $captainClaimRepository,
        private SessionClaimRepository $sessionClaimRepository,
        private TournamentSessionTeamAnswerRepository $answerRepository,
        private AppealRepository $appealRepository,
        private TournamentOfficialRepository $officialRepository,
    ) {
    }

    public function countPendingTournamentModeration(): int
    {
        return $this->tournamentModerationClaimRepository->countPending();
    }

    public function countPendingCaptainClaims(): int
    {
        return $this->captainClaimRepository->countPending();
    }

    public function countPendingOrganizerClaims(Player $player): int
    {
        return $this->sessionClaimRepository->countPendingForOrganizer($player);
    }

    public function countSubmittedDisputesForJury(Player $player): int
    {
        return $this->answerRepository->countSubmittedDisputesForJury($player);
    }

    public function countPendingAppealsForJury(Player $player): int
    {
        return $this->appealRepository->countPendingForJury($player);
    }

    public function getInvolvedTournamentIds(Player $player): array
    {
        return $this->officialRepository->findTournamentIdsByPlayerAndRoles(
            $player,
            [
                TournamentOfficialRole::Organizer,
                TournamentOfficialRole::GameJury,
                TournamentOfficialRole::AppealJury,
            ],
        );
    }
}
