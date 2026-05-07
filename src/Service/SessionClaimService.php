<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\Session\ClaimRequestDTO;
use App\DTO\Request\Session\RejectRequestDTO;
use App\DTO\Request\Session\UpdateRequestDTO;
use App\Entity\Player;
use App\Entity\SessionClaim;
use App\Enum\SessionClaimStatus;
use App\Entity\Tournament;
use App\Entity\TournamentSession;
use App\Entity\User;
use App\Repository\PlayerRepository;
use App\Repository\SessionClaimRepository;
use App\Repository\TournamentOfficialRepository;
use App\Repository\TournamentSessionTeamRepository;
use App\Repository\VenueRepresentativeRepository;
use App\Repository\VenueRepository;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

class SessionClaimService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SessionClaimRepository $claimRepository,
        private VenueRepository $venueRepository,
        private PlayerRepository $playerRepository,
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private VenueRepresentativeRepository $representativeRepository,
        private TournamentOfficialRepository $officialRepository,
    ) {
    }

    /**
     * @throws DateMalformedStringException
     * @throws LogicException
     */
    public function submit(Tournament $tournament, User $user, ClaimRequestDTO $dto): void
    {
        $player = $user->getPlayer()
            ?? throw new LogicException('common.error');

        $venue = $this->venueRepository->find($dto->venueId)
            ?? throw new LogicException('common.not_found');

        if (!$this->representativeRepository->isRepresentative($player, $venue)) {
            throw new LogicException('session_claim.error.not_representative');
        }

        $playedAt = $dto->playedAt !== null ? new DateTimeImmutable($dto->playedAt) : null;
        $this->validateDate($tournament, $playedAt);

        $host = $dto->hostId !== null ? $this->playerRepository->find($dto->hostId) : null;

        $session = new TournamentSession();
        $session->setTournament($tournament);
        $session->setVenue($venue);
        $session->setRepresentative($player);
        $session->setPlayedAt($playedAt);
        $session->setEstimatedTeams($dto->estimatedTeams);
        $session->setHost($host);

        $claim = new SessionClaim();
        $claim->setSession($session);
        $claim->setPlayer($player);

        $this->em->persist($session);
        $this->em->persist($claim);
        $this->em->flush();
    }

    /**
     * @throws DateMalformedStringException
     * @throws LogicException
     */
    public function update(TournamentSession $session, User $user, UpdateRequestDTO $dto): void
    {
        $this->ensureSessionOwner($user, $session);

        $playedAt = $dto->playedAt !== null ? new DateTimeImmutable($dto->playedAt) : null;
        $this->validateDate($session->getTournament(), $playedAt);

        $session->setPlayedAt($playedAt);
        $session->setEstimatedTeams($dto->estimatedTeams);
        $session->setHost(
            $dto->hostId !== null ? $this->playerRepository->find($dto->hostId) : null,
        );

        $this->em->flush();
    }

    /**
     * @throws LogicException
     */
    public function approve(TournamentSession $session, User $user): void
    {
        $this->ensureOrganizer($user, $session->getTournament());

        $claim = $this->claimRepository->findBySession($session)
            ?? throw new LogicException('session_claim.error.no_claim');

        if ($claim->getStatus() !== SessionClaimStatus::Pending) {
            throw new LogicException('session_claim.error.not_pending');
        }

        $claim->setStatus(SessionClaimStatus::Approved);
        $claim->setResolvedAt(new DateTimeImmutable());

        $this->em->flush();
    }

    /**
     * @throws LogicException
     */
    public function reject(TournamentSession $session, User $user, RejectRequestDTO $dto): void
    {
        $this->ensureOrganizer($user, $session->getTournament());

        $claim = $this->claimRepository->findBySession($session)
            ?? throw new LogicException('session_claim.error.no_claim');

        if ($claim->getStatus() !== SessionClaimStatus::Pending) {
            throw new LogicException('session_claim.error.not_pending');
        }

        $claim->setStatus(SessionClaimStatus::Rejected);
        $claim->setComment($dto->comment);
        $claim->setResolvedAt(new DateTimeImmutable());

        $this->em->flush();
    }

    /**
     * @throws LogicException
     */
    public function resubmit(TournamentSession $session, User $user): void
    {
        $this->ensureSessionOwner($user, $session);
        $this->validateDate($session->getTournament(), $session->getPlayedAt());

        $claim = $this->claimRepository->findBySession($session)
            ?? throw new LogicException('session_claim.error.no_claim');

        if ($claim->getStatus() !== SessionClaimStatus::Rejected) {
            throw new LogicException('session_claim.error.not_rejected');
        }

        $claim->setStatus(SessionClaimStatus::Pending);
        $claim->setComment(null);
        $claim->setCreatedAt(new DateTimeImmutable());
        $claim->setResolvedAt(null);

        $this->em->flush();
    }

    /**
     * @throws LogicException
     */
    public function delete(TournamentSession $session, User $user): void
    {
        $this->ensureSessionOwner($user, $session);

        $teamCounts = $this->sessionTeamRepository->countBySessionIds([$session->getId()]);
        if (($teamCounts[$session->getId()] ?? 0) > 0) {
            throw new LogicException('session_claim.error.has_results');
        }

        $claim = $this->claimRepository->findBySession($session);

        if ($claim !== null) {
            $this->em->remove($claim);
        }

        $this->em->remove($session);
        $this->em->flush();
    }

    /**
     * @throws LogicException
     */
    private function ensureOrganizer(User $user, Tournament $tournament): void
    {
        $player = $user->getPlayer()
            ?? throw new LogicException('common.error');

        if (!$this->officialRepository->isOrganizer($player, $tournament)) {
            throw new LogicException('common.error');
        }
    }

    /**
     * @throws LogicException
     */
    private function ensureSessionOwner(User $user, TournamentSession $session): void
    {
        $player = $user->getPlayer()
            ?? throw new LogicException('common.error');

        if ($session->getRepresentative()->getId() !== $player->getId()) {
            throw new LogicException('common.error');
        }
    }

    /**
     * @throws LogicException
     */
    private function validateDate(Tournament $tournament, ?DateTimeImmutable $playedAt): void
    {
        if ($playedAt === null) {
            return;
        }

        if ($tournament->getStartedAt() !== null && $playedAt < $tournament->getStartedAt()) {
            throw new LogicException('session_claim.error.date_before_start');
        }

        if ($tournament->getEndedAt() !== null && $playedAt > $tournament->getEndedAt()) {
            throw new LogicException('session_claim.error.date_after_end');
        }
    }
}
