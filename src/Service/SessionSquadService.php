<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\Session\SquadPlayerDTO;
use App\DTO\Request\Session\SquadRequestDTO;
use App\Entity\Player;
use App\Entity\Team;
use App\Entity\TournamentSession;
use App\Entity\TournamentSessionTeam;
use App\Entity\TournamentSessionTeamPlayer;
use App\Enum\SessionClaimStatus;
use App\Repository\PlayerRepository;
use App\Repository\SessionClaimRepository;
use App\Repository\TeamPlayerRepository;
use App\Repository\TeamRepository;
use App\Repository\TournamentSessionTeamRepository;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Repository\TownRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SessionSquadService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TeamRepository $teamRepository,
        private TownRepository $townRepository,
        private PlayerRepository $playerRepository,
        private TeamPlayerRepository $teamPlayerRepository,
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
        private SessionClaimRepository $claimRepository,
    ) {
    }

    /**
     * @throws AccessDeniedHttpException
     */
    public function ensureCanManageSquad(TournamentSession $session, Player $player): void
    {
        if ($session->getRepresentative()->getId() !== $player->getId()) {
            throw new AccessDeniedHttpException();
        }

        $claim = $this->claimRepository->findBySession($session);
        if (
            $claim === null
            || $claim->getStatus() !== SessionClaimStatus::Approved
            || $session->getPlayedAt() === null
            || $session->getPlayedAt() > new DateTimeImmutable('today')
        ) {
            throw new AccessDeniedHttpException();
        }
    }

    /**
     * @throws AccessDeniedHttpException
     * @throws LogicException
     */
    public function saveSquad(TournamentSession $session, Player $representative, SquadRequestDTO $dto): void
    {
        $this->ensureCanManageSquad($session, $representative);

        $team = $this->resolveTeam($dto);
        $this->validateNoDuplicate($session, $team);

        $players = $this->resolveAndValidatePlayers($session, $dto, []);
        $captainIndex = $this->validateCaptainIndex($dto, $players);

        $sessionTeam = new TournamentSessionTeam();
        $sessionTeam->setTournamentSession($session);
        $sessionTeam->setTeam($team);
        $sessionTeam->setOneTimeName($dto->oneTimeName);

        $this->em->persist($sessionTeam);

        $baseSquadPlayerIds = $this->getBaseSquadPlayerIds($session, $team);

        foreach ($players as $index => $player) {
            $sessionTeamPlayer = new TournamentSessionTeamPlayer();
            $sessionTeamPlayer->setTournamentSessionTeam($sessionTeam);
            $sessionTeamPlayer->setPlayer($player);
            $sessionTeamPlayer->setIsCaptain($index === $captainIndex);
            $sessionTeamPlayer->setIsLegionary(!in_array($player->getId(), $baseSquadPlayerIds, true));

            $this->em->persist($sessionTeamPlayer);
        }

        $this->em->flush();
    }

    /**
     * @throws LogicException
     */
    private function resolveTeam(SquadRequestDTO $dto): Team
    {
        if ($dto->teamId !== null) {
            return $this->teamRepository->find($dto->teamId)
                ?? throw new LogicException('common.not_found');
        }

        if ($dto->teamName === null || trim($dto->teamName) === '') {
            throw new LogicException('squad.error.team_required');
        }

        if ($dto->townId === null) {
            throw new LogicException('squad.error.town_required');
        }

        $town = $this->townRepository->find($dto->townId)
            ?? throw new LogicException('common.not_found');

        $team = new Team();
        $team->setName(trim($dto->teamName));
        $team->setTown($town);

        $this->em->persist($team);

        return $team;
    }

    /**
     * @throws LogicException
     */
    private function validateNoDuplicate(TournamentSession $session, Team $team): void
    {
        if ($team->getId() === null) {
            return;
        }

        $tournament = $session->getTournament();
        $teamIds = $this->sessionTeamRepository->findTeamIdsByTournament($tournament);

        if (in_array($team->getId(), $teamIds, true)) {
            throw new LogicException('squad.error.team_already_added');
        }
    }

    /**
     * @return list<int>
     */
    private function getBaseSquadPlayerIds(TournamentSession $session, Team $team): array
    {
        $season = $session->getTournament()->getSeason();
        if ($season === null) {
            return [];
        }

        $squadMap = $this->teamPlayerRepository->getSquadMapBySeason($season);

        return $squadMap[$team->getId()]['playerIds'] ?? [];
    }

    /**
     * @return list<Player>
     * @throws LogicException
     */
    private function resolvePlayersForUpdate(
        TournamentSession $session,
        TournamentSessionTeam $sessionTeam,
        SquadRequestDTO $dto,
    ): array {
        $currentPlayers = $this->sessionTeamPlayerRepository->findBySessionTeamIds([$sessionTeam->getId()]);
        $excludePlayerIds = array_map(
            static fn(TournamentSessionTeamPlayer $stp) => $stp->getPlayer()->getId(),
            $currentPlayers,
        );

        return $this->resolveAndValidatePlayers($session, $dto, $excludePlayerIds);
    }

    /**
     * @param TournamentSession $session
     * @param SquadRequestDTO $dto
     * @param list<int|null> $excludePlayerIds Player IDs to exclude from "already played" check
     * @return list<Player>
     * @throws LogicException
     */
    private function resolveAndValidatePlayers(
        TournamentSession $session,
        SquadRequestDTO $dto,
        array $excludePlayerIds,
    ): array {
        if ($dto->players === []) {
            throw new LogicException('squad.error.min_players');
        }

        if (count($dto->players) > 8) {
            throw new LogicException('squad.error.max_players');
        }

        $tournament = $session->getTournament();
        $usedPlayerIds = $this->sessionTeamPlayerRepository->findPlayerIdsByTournament($tournament);
        $usedPlayerIds = array_diff($usedPlayerIds, $excludePlayerIds);

        // Batch-load existing players
        $existingIds = array_filter(array_map(
            static fn(SquadPlayerDTO $playerDto) => $playerDto->id,
            $dto->players,
        ));
        $existingPlayers = $existingIds !== []
            ? $this->playerRepository->findBy(['id' => $existingIds])
            : [];
        $existingPlayersById = [];
        foreach ($existingPlayers as $player) {
            $existingPlayersById[$player->getId()] = $player;
        }

        $players = [];
        $seenIds = [];

        foreach ($dto->players as $playerDto) {
            $player = $this->resolvePlayer($playerDto, $existingPlayersById);

            if ($player->getId() !== null) {
                if (in_array($player->getId(), $seenIds, true)) {
                    throw new LogicException('squad.error.duplicate_players');
                }

                if (in_array($player->getId(), $usedPlayerIds, true)) {
                    throw new LogicException(
                        'squad.error.player_already_played:' . $player->getFullName(),
                    );
                }

                $seenIds[] = $player->getId();
            }

            $players[] = $player;
        }

        return $players;
    }

    /**
     * @param array<int, Player> $preloadedPlayers
     * @throws LogicException
     */
    private function resolvePlayer(SquadPlayerDTO $dto, array $preloadedPlayers = []): Player
    {
        if ($dto->id !== null) {
            return $preloadedPlayers[$dto->id]
                ?? throw new LogicException('common.not_found');
        }

        if ($dto->lastName === null || trim($dto->lastName) === '') {
            throw new LogicException('squad.error.player_last_name_required');
        }

        if ($dto->firstName === null || trim($dto->firstName) === '') {
            throw new LogicException('squad.error.player_first_name_required');
        }

        $player = new Player();
        $player->setLastName(trim($dto->lastName));
        $player->setFirstName(trim($dto->firstName));
        $player->setPatronymic($dto->patronymic !== null ? trim($dto->patronymic) : null);

        if ($dto->townId !== null) {
            $town = $this->townRepository->find($dto->townId);
            $player->setTown($town);
        }

        $this->em->persist($player);

        return $player;
    }

    /**
     * @param SquadRequestDTO $dto
     * @param list<Player> $players
     * @return int
     * @throws LogicException
     */
    private function validateCaptainIndex(SquadRequestDTO $dto, array $players): int
    {
        if ($dto->captainIndex === null || $dto->captainIndex >= count($players)) {
            throw new LogicException('squad.error.captain_required');
        }

        return $dto->captainIndex;
    }

    /**
     * @throws AccessDeniedHttpException
     * @throws LogicException
     */
    public function updateSquad(TournamentSessionTeam $sessionTeam, Player $representative, SquadRequestDTO $dto): void
    {
        $session = $sessionTeam->getTournamentSession();
        $this->ensureCanManageSquad($session, $representative);

        $players = $this->resolvePlayersForUpdate($session, $sessionTeam, $dto);
        $captainIndex = $this->validateCaptainIndex($dto, $players);

        $sessionTeam->setOneTimeName($dto->oneTimeName);

        $existingEntries = $this->sessionTeamPlayerRepository->findBySessionTeamIds([$sessionTeam->getId()]);
        $existingByPlayerId = [];
        foreach ($existingEntries as $entry) {
            $existingByPlayerId[$entry->getPlayer()->getId()] = $entry;
        }

        $baseSquadPlayerIds = $this->getBaseSquadPlayerIds($session, $sessionTeam->getTeam());

        $newPlayerIds = [];
        foreach ($players as $index => $player) {
            $playerId = $player->getId();
            $newPlayerIds[] = $playerId;
            $isCaptain = $index === $captainIndex;
            $isLegionary = !in_array($playerId, $baseSquadPlayerIds, true);

            if ($playerId !== null && isset($existingByPlayerId[$playerId])) {
                $entry = $existingByPlayerId[$playerId];
                $entry->setIsCaptain($isCaptain);
                $entry->setIsLegionary($isLegionary);
            } else {
                $sessionTeamPlayer = new TournamentSessionTeamPlayer();
                $sessionTeamPlayer->setTournamentSessionTeam($sessionTeam);
                $sessionTeamPlayer->setPlayer($player);
                $sessionTeamPlayer->setIsCaptain($isCaptain);
                $sessionTeamPlayer->setIsLegionary($isLegionary);
                $this->em->persist($sessionTeamPlayer);
            }
        }

        foreach ($existingByPlayerId as $playerId => $entry) {
            if (!in_array($playerId, $newPlayerIds, true)) {
                $this->em->remove($entry);
            }
        }

        $this->em->flush();
    }

    /**
     * @throws AccessDeniedHttpException
     * @throws LogicException
     */
    public function deleteSquad(TournamentSessionTeam $sessionTeam, Player $representative): void
    {
        $session = $sessionTeam->getTournamentSession();
        $this->ensureCanManageSquad($session, $representative);

        $existingPlayers = $this->sessionTeamPlayerRepository->findBySessionTeamIds([$sessionTeam->getId()]);
        foreach ($existingPlayers as $existing) {
            $this->em->remove($existing);
        }

        $this->em->remove($sessionTeam);
        $this->em->flush();
    }
}
