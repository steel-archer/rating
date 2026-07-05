<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Classic\Entity\CaptainClaim;
use App\Classic\Entity\Team;
use App\Classic\Entity\TeamPlayer;
use App\Classic\Entity\TeamPlayerTransfer;
use App\Classic\Enum\CaptainClaimStatus;
use App\Classic\Enum\TeamPlayerTransferType;
use App\Classic\Repository\CaptainClaimRepository;
use App\Classic\Repository\TeamPlayerRepository;
use App\Common\Entity\Player;
use App\Common\Enum\CacheTag;
use App\Common\Repository\SeasonRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class CaptainClaimService
{
    public function __construct(
        private CaptainClaimRepository $claimRepository,
        private TeamPlayerRepository $teamPlayerRepository,
        private SeasonRepository $seasonRepository,
        private EntityManagerInterface $em,
        private TagAwareCacheInterface $cache,
    ) {
    }

    /**
     * @throws LogicException
     * @throws NonUniqueResultException
     */
    public function submit(Player $player, Team $team, string $comment): void
    {
        $season = $this->seasonRepository->findCurrent()
            ?? throw new LogicException('captain_claim.error.no_season');

        // Player must not already have a pending claim
        $existing = $this->claimRepository->findPendingByPlayer($player);
        if ($existing !== null) {
            throw new LogicException('captain_claim.error.already_has_pending');
        }

        // Player must not be in base squad of another team
        $playerEntry = $this->teamPlayerRepository->findOneBy([
            'player' => $player,
            'season' => $season,
        ]);

        if ($playerEntry !== null && $playerEntry->getTeam()->getId() !== $team->getId()) {
            throw new LogicException('captain_claim.error.in_another_team');
        }

        // Player must not already be captain of this team
        if ($playerEntry !== null && $playerEntry->isCaptain()) {
            throw new LogicException('captain_claim.error.already_captain');
        }

        $claim = new CaptainClaim();
        $claim->setPlayer($player);
        $claim->setTeam($team);
        $claim->setComment(trim($comment));

        $this->em->persist($claim);
        $this->em->flush();
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws NonUniqueResultException
     */
    public function approve(CaptainClaim $claim): void
    {
        if ($claim->getStatus() !== CaptainClaimStatus::Pending) {
            throw new LogicException('captain_claim.error.already_resolved');
        }

        $season = $this->seasonRepository->findCurrent()
            ?? throw new LogicException('captain_claim.error.no_season');

        $team = $claim->getTeam();
        $player = $claim->getPlayer();

        // Validate max players
        $currentSquad = $this->teamPlayerRepository->findByTeamAndSeason($team, $season);
        $playerInSquad = false;

        foreach ($currentSquad as $tp) {
            if ($tp->getPlayer()->getId() === $player->getId()) {
                $playerInSquad = true;
                break;
            }
        }

        if (!$playerInSquad && count($currentSquad) >= TeamManagementService::MAX_PLAYERS) {
            throw new LogicException('captain_claim.error.team_full');
        }

        // Ensure player hasn't joined another team since submitting
        if (!$playerInSquad) {
            $existingEntry = $this->teamPlayerRepository->findOneBy([
                'player' => $player,
                'season' => $season,
            ]);
            if ($existingEntry !== null && $existingEntry->getTeam()->getId() !== $team->getId()) {
                throw new LogicException('captain_claim.error.in_another_team');
            }
        }

        // Remove current captain flag
        $currentCaptainPlayer = $this->findCurrentCaptainPlayer($currentSquad);
        if ($currentCaptainPlayer !== null) {
            $currentCaptain = $this->teamPlayerRepository->findCaptainEntry($currentCaptainPlayer, $season);
            $currentCaptain?->setIsCaptain(false);
        }

        // Add player to squad if not already there
        if (!$playerInSquad) {
            $teamPlayer = new TeamPlayer();
            $teamPlayer->setTeam($team);
            $teamPlayer->setPlayer($player);
            $teamPlayer->setSeason($season);
            $teamPlayer->setIsCaptain(true);

            $this->em->persist($teamPlayer);

            $transfer = new TeamPlayerTransfer();
            $transfer->setPlayer($player);
            $transfer->setTeam($team);
            $transfer->setSeason($season);
            $transfer->setType(TeamPlayerTransferType::Joined);
            $transfer->setDate(new DateTimeImmutable('today'));

            $this->em->persist($transfer);
        } else {
            // Player already in squad — just set captain
            foreach ($currentSquad as $tp) {
                if ($tp->getPlayer()->getId() === $player->getId()) {
                    $tp->setIsCaptain(true);
                    break;
                }
            }
        }

        $claim->setStatus(CaptainClaimStatus::Approved);
        $claim->setResolvedAt(new DateTimeImmutable());

        $this->em->flush();

        $this->cache->invalidateTags([
            CacheTag::team($team->getId()),
            CacheTag::playerSquad($player->getId()),
        ]);
    }

    /**
     * @throws LogicException
     */
    public function reject(CaptainClaim $claim, string $comment): void
    {
        if ($claim->getStatus() !== CaptainClaimStatus::Pending) {
            throw new LogicException('captain_claim.error.already_resolved');
        }

        $claim->setStatus(CaptainClaimStatus::Rejected);
        $claim->setModeratorComment(trim($comment));
        $claim->setResolvedAt(new DateTimeImmutable());

        $this->em->flush();
    }

    /**
     * Checks whether approval is possible (for UI: disable button if not).
     */
    public function canApprove(CaptainClaim $claim): bool
    {
        $season = $this->seasonRepository->findCurrent();
        if ($season === null) {
            return false;
        }

        $team = $claim->getTeam();
        $player = $claim->getPlayer();

        $currentSquad = $this->teamPlayerRepository->findByTeamAndSeason($team, $season);

        // Already in squad, can become captain
        if (array_any($currentSquad, fn($tp) => $tp->getPlayer()->getId() === $player->getId())) {
            return true;
        }

        // Check if player joined another team since submitting
        $existingEntry = $this->teamPlayerRepository->findOneBy([
            'player' => $player,
            'season' => $season,
        ]);
        if ($existingEntry !== null && $existingEntry->getTeam()->getId() !== $team->getId()) {
            return false;
        }

        return count($currentSquad) < TeamManagementService::MAX_PLAYERS;
    }

    /**
     * @param list<TeamPlayer> $squad
     */
    private function findCurrentCaptainPlayer(array $squad): ?Player
    {
        foreach ($squad as $tp) {
            if ($tp->isCaptain()) {
                return $tp->getPlayer();
            }
        }

        return null;
    }
}
