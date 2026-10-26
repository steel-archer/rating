<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Classic\DTO\Response\My\SessionClaimListDTO;
use App\Classic\DTO\Response\My\SessionClaimListView;
use App\Classic\Entity\SessionClaim;
use App\Classic\Repository\SessionClaimRepository;
use App\Common\Entity\Player;
use App\Common\Mapping\Mapper;

/**
 * Assembles the session claims list view for a venue representative: loads the
 * shared claims, derives each claim's work state and submission-deadline
 * urgency, maps them to DTOs and splits the result into the "current" and
 * "closed" blocks with the current block ordered by how urgent the action is.
 */
final class SessionClaimListBuilder
{
    public function __construct(
        private readonly SessionClaimRepository $claimRepository,
        private readonly SessionClaimWorkStateResolver $workStateResolver,
        private readonly Mapper $mapper,
    ) {
    }

    /**
     * Build the full list view for the given player.
     *
     * @param Player $player
     * @return SessionClaimListView
     */
    public function build(Player $player): SessionClaimListView
    {
        $sessionClaims = $this->claimRepository->findByVenueRepresentative($player);

        $sessionIds = array_map(
            static fn(SessionClaim $claim): int => $claim->getSession()->getId(),
            $sessionClaims,
        );

        $actualTeamsBySession = $this->claimRepository->countSubmittedTeamsBySessionIds($sessionIds);

        $workStateBySession = [];
        $deadlineUrgencyBySession = [];
        $deadlineUrgencyByTournament = [];
        foreach ($sessionClaims as $claim) {
            $session = $claim->getSession();
            $tournament = $session->getTournament();
            $tournamentId = $tournament->getId();

            $workStateBySession[$session->getId()] = $this->workStateResolver->resolve($claim);

            // Urgency depends only on the tournament, so resolve it once per tournament.
            if (!array_key_exists($tournamentId, $deadlineUrgencyByTournament)) {
                $deadlineUrgencyByTournament[$tournamentId] = $this->workStateResolver->resolveDeadlineUrgency($tournament);
            }
            $deadlineUrgencyBySession[$session->getId()] = $deadlineUrgencyByTournament[$tournamentId];
        }

        /** @var list<SessionClaimListDTO> $claims */
        $claims = $this->mapper->mapMultiple(
            $sessionClaims,
            SessionClaimListDTO::class,
            [
                'actualTeamsBySession' => $actualTeamsBySession,
                'workStateBySession' => $workStateBySession,
                'deadlineUrgencyBySession' => $deadlineUrgencyBySession,
            ],
        );

        $currentClaims = [];
        $closedClaims = [];
        foreach ($claims as $claim) {
            if ($claim->workState->isCurrent()) {
                $currentClaims[] = $claim;
            } else {
                $closedClaims[] = $claim;
            }
        }

        usort($currentClaims, $this->currentOrdering(...));

        return new SessionClaimListView($currentClaims, $closedClaims);
    }

    /**
     * Order the "current" block so actionable claims float to the top: results
     * needed first, then moderation and awaiting game, rejected last. Within the
     * same work state the earlier play date comes first (soonest work first).
     *
     * @param SessionClaimListDTO $left
     * @param SessionClaimListDTO $right
     * @return int
     */
    private function currentOrdering(SessionClaimListDTO $left, SessionClaimListDTO $right): int
    {
        $byPriority = $left->workState->sortPriority() <=> $right->workState->sortPriority();
        if ($byPriority !== 0) {
            return $byPriority;
        }

        $leftPlayedAt = $left->playedAt?->getTimestamp() ?? PHP_INT_MAX;
        $rightPlayedAt = $right->playedAt?->getTimestamp() ?? PHP_INT_MAX;

        return $leftPlayedAt <=> $rightPlayedAt;
    }
}
