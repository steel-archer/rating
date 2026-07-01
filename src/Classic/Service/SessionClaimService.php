<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Classic\DTO\Request\Session\ClaimRequestDTO;
use App\Classic\DTO\Request\Session\RejectRequestDTO;
use App\Classic\DTO\Request\Session\UpdateRequestDTO;
use App\Classic\DTO\Response\My\SessionClaimGroupDTO;
use App\Classic\DTO\Response\Tournament\SessionClaimDTO;
use App\Common\Entity\Player;
use App\Classic\Entity\SessionClaim;
use App\Classic\Enum\SessionClaimStatus;
use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentSession;
use App\Classic\Enum\TournamentOnlineMode;
use App\Common\Mapping\Mapper;
use App\Common\Repository\PlayerRepository;
use App\Classic\Repository\AppealRepository;
use App\Classic\Repository\SessionClaimRepository;
use App\Classic\Repository\TournamentOfficialRepository;
use App\Classic\Repository\TournamentSessionRepository;
use App\Classic\Repository\TournamentSessionTeamAnswerRepository;
use App\Classic\Repository\TournamentSessionTeamPlayerRepository;
use App\Classic\Repository\TournamentSessionTeamRepository;
use App\Common\Repository\VenueRepresentativeRepository;
use App\Common\Repository\VenueRepository;
use App\Classic\Service\Cache\CacheInvalidator;
use App\Common\Service\UserContactsService;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Cache\InvalidArgumentException;

class SessionClaimService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SessionClaimRepository $claimRepository,
        private VenueRepository $venueRepository,
        private PlayerRepository $playerRepository,
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private TournamentSessionRepository $sessionRepository,
        private TournamentSessionTeamAnswerRepository $answerRepository,
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
        private AppealRepository $appealRepository,
        private VenueRepresentativeRepository $representativeRepository,
        private TournamentOfficialRepository $officialRepository,
        private CacheInvalidator $cacheInvalidator,
        private UserContactsService $contactsService,
        private Mapper $mapper,
    ) {
    }

    /**
     * @return list<SessionClaimGroupDTO>
     */
    public function getPendingClaimsByOrganizer(Player $player): array
    {
        return $this->buildClaimGroups($this->claimRepository->findPendingByOrganizer($player));
    }

    /**
     * @return list<SessionClaimGroupDTO>
     */
    public function getActiveClaimsByOrganizer(Player $player): array
    {
        return $this->buildClaimGroups($this->claimRepository->findActiveByOrganizer($player));
    }

    /**
     * @param list<SessionClaim> $claims
     * @return list<SessionClaimGroupDTO>
     */
    private function buildClaimGroups(array $claims): array
    {
        $grouped = [];
        $venueIds = [];
        foreach ($claims as $claim) {
            $tournament = $claim->getSession()->getTournament();
            $tournamentId = $tournament->getId();
            if (!isset($grouped[$tournamentId])) {
                $grouped[$tournamentId] = [
                    'tournament' => $tournament,
                    'claims' => [],
                ];
            }
            $grouped[$tournamentId]['claims'][] = $claim;
            $venueIds[] = $claim->getSession()->getVenue()->getId();
        }

        $venueSessionCounts = $venueIds
                |> array_unique(...)
                |> array_values(...)
                |> $this->sessionRepository->countPlayedByVenueIds(...);

        return array_values(array_map(
            function (array $group) use ($venueSessionCounts) {
                /** @var list<SessionClaimDTO> $claims */
                $claims = array_map(
                    fn(SessionClaim $claim) => $this->mapper->map(
                        $claim,
                        SessionClaimDTO::class,
                        [
                            'playerContacts' => $this->contactsService->getContacts($claim->getPlayer()->getUser()),
                            'venueSessionCounts' => $venueSessionCounts,
                        ],
                    ),
                    $group['claims'],
                );

                return new SessionClaimGroupDTO(
                    tournamentId: $group['tournament']->getId(),
                    tournamentName: $group['tournament']->getName(),
                    claims: $claims,
                );
            },
            $grouped,
        ));
    }

    /**
     * @throws DateMalformedStringException
     * @throws LogicException
     */
    public function submit(Tournament $tournament, Player $player, ClaimRequestDTO $dto): void
    {
        if (!$tournament->isRegistrationOpen()) {
            throw new LogicException('session_claim.error.registration_closed');
        }

        $venue = $this->venueRepository->find($dto->venueId)
            ?? throw new LogicException('common.not_found');

        if (!$this->representativeRepository->isRepresentative($player, $venue)) {
            throw new LogicException('session_claim.error.not_representative');
        }

        $onlineMode = $tournament->getOnlineMode();
        if ($onlineMode === TournamentOnlineMode::Offline && $venue->isOnline()) {
            throw new LogicException('session_claim.error.online_venue_offline_tournament');
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

        $isOnline = match ($onlineMode) {
            TournamentOnlineMode::Online => true,
            TournamentOnlineMode::Offline => false,
            TournamentOnlineMode::Mixed => $venue->isOnline() || $dto->isOnline,
        };
        $session->setIsOnline($isOnline);

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
    public function update(TournamentSession $session, Player $player, UpdateRequestDTO $dto): void
    {
        $this->ensureSessionOwner($player, $session);

        if (!$session->getTournament()->isRegistrationOpen()) {
            throw new LogicException('session_claim.error.registration_closed');
        }

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
     * @throws InvalidArgumentException
     */
    public function approve(TournamentSession $session, Player $player): void
    {
        $this->ensureOrganizer($player, $session->getTournament());

        $claim = $this->claimRepository->findBySession($session)
            ?? throw new LogicException('session_claim.error.no_claim');

        if ($claim->getStatus() !== SessionClaimStatus::Pending) {
            throw new LogicException('session_claim.error.not_pending');
        }

        $claim->setStatus(SessionClaimStatus::Approved);
        $claim->setResolvedAt(new DateTimeImmutable());

        $this->em->flush();
        $this->cacheInvalidator->invalidateTournament($session->getTournament());
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function reject(TournamentSession $session, Player $player, RejectRequestDTO $dto): void
    {
        $this->ensureOrganizer($player, $session->getTournament());

        $claim = $this->claimRepository->findBySession($session)
            ?? throw new LogicException('session_claim.error.no_claim');

        if ($claim->getStatus() !== SessionClaimStatus::Pending) {
            throw new LogicException('session_claim.error.not_pending');
        }

        $claim->setStatus(SessionClaimStatus::Rejected);
        $claim->setComment($dto->comment);
        $claim->setResolvedAt(new DateTimeImmutable());

        $this->em->flush();
        $this->cacheInvalidator->invalidateTournament($session->getTournament());
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function revoke(TournamentSession $session, Player $player): void
    {
        $this->ensureOrganizer($player, $session->getTournament());

        $claim = $this->claimRepository->findBySession($session)
            ?? throw new LogicException('session_claim.error.no_claim');

        if ($claim->getStatus() !== SessionClaimStatus::Approved) {
            throw new LogicException('session_claim.error.not_approved');
        }

        $this->deleteSessionData($session);

        $claim->setStatus(SessionClaimStatus::Revoked);
        $claim->setResolvedAt(new DateTimeImmutable());

        $this->em->flush();
        $this->cacheInvalidator->invalidateTournamentWithParticipants($session->getTournament());
    }

    private function deleteSessionData(TournamentSession $session): void
    {
        $sessionTeamIds = $this->sessionTeamRepository->findIdsBySession($session);

        if ($sessionTeamIds === []) {
            return;
        }

        $answerIds = $this->answerRepository->findIdsBySessionTeamIds($sessionTeamIds);
        $this->appealRepository->deleteByAnswerIds($answerIds);
        $this->answerRepository->deleteBySessionTeamIds($sessionTeamIds);
        $this->sessionTeamPlayerRepository->deleteBySessionTeamIds($sessionTeamIds);
        $this->sessionTeamRepository->deleteByIds($sessionTeamIds);
    }

    /**
     * @throws LogicException
     */
    public function resubmit(TournamentSession $session, Player $player): void
    {
        $this->ensureSessionOwner($player, $session);

        if (!$session->getTournament()->isRegistrationOpen()) {
            throw new LogicException('session_claim.error.registration_closed');
        }

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
    public function delete(TournamentSession $session, Player $player): void
    {
        $this->ensureSessionOwner($player, $session);

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
    private function ensureOrganizer(Player $player, Tournament $tournament): void
    {
        if (!$this->officialRepository->isOrganizer($player, $tournament)) {
            throw new LogicException('common.error');
        }
    }

    /**
     * @throws LogicException
     */
    private function ensureSessionOwner(Player $player, TournamentSession $session): void
    {
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
