<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\TeamManagement\AddPlayerRequestDTO;
use App\DTO\Request\TeamManagement\RemovePlayerRequestDTO;
use App\DTO\Request\TeamManagement\SetCaptainRequestDTO;
use App\DTO\Request\TeamManagement\UpdateTeamRequestDTO;
use App\DTO\Response\My\TeamManagementDTO;
use App\DTO\Response\My\TeamManagementPlayerDTO;
use App\Entity\Player;
use App\Entity\Season;
use App\Entity\Team;
use App\Entity\TeamPlayer;
use App\Enum\CacheTag;
use App\Mapping\Mapper;
use App\Repository\PlayerRepository;
use App\Repository\SeasonRepository;
use App\Repository\TeamPlayerRepository;
use App\Repository\TeamRepository;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Repository\TownRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class TeamManagementService
{
    private const int MAX_PLAYERS = 8;

    public function __construct(
        private TeamPlayerRepository $teamPlayerRepository,
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
        private SeasonRepository $seasonRepository,
        private PlayerRepository $playerRepository,
        private TeamRepository $teamRepository,
        private TownRepository $townRepository,
        private EntityManagerInterface $entityManager,
        private Mapper $mapper,
        private TagAwareCacheInterface $cache,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     */
    public function isInBaseSquad(Player $player): bool
    {
        $playerId = $player->getId();
        $cacheKey = "player_in_base_squad_$playerId";

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($player, $playerId) {
            $item->tag([CacheTag::playerSquad($playerId)]);
            $item->expiresAfter(3600);

            $season = $this->seasonRepository->findCurrent();
            if ($season === null) {
                return false;
            }

            $entries = $this->teamPlayerRepository->findBy([
                'player' => $player,
                'season' => $season,
            ]);

            return $entries !== [];
        });
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getForPlayer(Player $player): ?TeamManagementDTO
    {
        $season = $this->seasonRepository->findCurrent();
        if ($season === null) {
            return null;
        }

        // Check if player is captain
        $captainEntry = $this->teamPlayerRepository->findCaptainEntry($player, $season);
        $isCaptain = $captainEntry !== null;

        // Find team: either as captain or as regular member
        if ($isCaptain) {
            $team = $captainEntry->getTeam();
        } else {
            $team = $this->resolvePlayerTeamOrNull($player, $season);
            if ($team === null) {
                return null;
            }
        }

        $teamPlayers = $this->teamPlayerRepository->findByTeamAndSeason($team, $season);

        /** @var list<TeamManagementPlayerDTO> $playerDtos */
        $playerDtos = $this->mapper->mapMultiple($teamPlayers, TeamManagementPlayerDTO::class);

        $basePlayerIds = array_map(
            static fn(TeamPlayer $tp) => $tp->getPlayer()->getId(),
            $teamPlayers,
        );

        // Fetch played players for current and previous seasons in one query
        $previousSeason = $this->seasonRepository->findPrevious($season);
        $seasons = [$season];
        if ($previousSeason !== null) {
            $seasons[] = $previousSeason;
        }

        $playerIdsBySeason = $this->sessionTeamPlayerRepository->findPlayerIdsByTeamAndSeasons($team, $seasons);
        $currentSeasonPlayerIds = $playerIdsBySeason[$season->getId()] ?? [];

        // Season players: played in current season but not in base squad
        $seasonOnlyIds = array_values(array_diff($currentSeasonPlayerIds, $basePlayerIds));
        $seasonPlayerDtos = $this->mapPlayerIds($seasonOnlyIds);

        // Previous season players: not in base squad and not in current season played
        $previousSeasonPlayerDtos = [];
        if ($previousSeason !== null) {
            $prevSeasonPlayerIds = $playerIdsBySeason[$previousSeason->getId()] ?? [];
            $allCurrentIds = array_merge($basePlayerIds, $seasonOnlyIds);
            $prevOnlyIds = array_values(array_diff($prevSeasonPlayerIds, $allCurrentIds));
            $previousSeasonPlayerDtos = $this->mapPlayerIds($prevOnlyIds);
        }

        return new TeamManagementDTO(
            teamId: $team->getId(),
            teamName: $team->getName(),
            townId: $team->getTown()->getId(),
            townName: $team->getTown()->getName(),
            seasonName: $season->getName(),
            isCaptain: $isCaptain,
            players: $playerDtos,
            seasonPlayers: $seasonPlayerDtos,
            previousSeasonPlayers: $previousSeasonPlayerDtos,
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws NonUniqueResultException
     */
    public function addPlayer(Player $captain, AddPlayerRequestDTO $dto): void
    {
        [$team, $season] = $this->resolveTeamAndSeason($captain);

        $currentPlayers = $this->teamPlayerRepository->findByTeamAndSeason($team, $season);
        if (count($currentPlayers) >= self::MAX_PLAYERS) {
            throw new LogicException('team_management.error.max_players');
        }

        $player = $this->playerRepository->find($dto->playerId)
            ?? throw new LogicException('team_management.error.player_not_found');

        foreach ($currentPlayers as $teamPlayer) {
            if ($teamPlayer->getPlayer()->getId() === $dto->playerId) {
                throw new LogicException('team_management.error.already_in_team');
            }
        }

        $existingEntry = $this->teamPlayerRepository->findBy([
            'player' => $player,
            'season' => $season,
        ]);
        if ($existingEntry !== []) {
            throw new LogicException('team_management.error.player_in_another_team');
        }

        $teamPlayer = new TeamPlayer();
        $teamPlayer->setTeam($team);
        $teamPlayer->setPlayer($player);
        $teamPlayer->setSeason($season);

        $this->entityManager->persist($teamPlayer);
        $this->entityManager->flush();

        $this->invalidateTeamCache($team);
        $this->cache->invalidateTags([CacheTag::playerSquad($dto->playerId)]);
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws NonUniqueResultException
     */
    public function removePlayer(Player $captain, RemovePlayerRequestDTO $dto): void
    {
        [$team, $season] = $this->resolveTeamAndSeason($captain);

        if ($dto->playerId === $captain->getId()) {
            throw new LogicException('team_management.error.cannot_remove_self');
        }

        $targetEntry = $this->findPlayerEntry($team, $season, $dto->playerId);

        $this->entityManager->remove($targetEntry);
        $this->entityManager->flush();

        $this->invalidateTeamCache($team);
        $this->cache->invalidateTags([CacheTag::playerSquad($dto->playerId)]);
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws NonUniqueResultException
     */
    public function setCaptain(Player $currentCaptain, SetCaptainRequestDTO $dto): void
    {
        [$team, $season] = $this->resolveTeamAndSeason($currentCaptain);

        $captainEntry = $this->teamPlayerRepository->findCaptainEntry($currentCaptain, $season);
        $newCaptainEntry = $this->findPlayerEntry($team, $season, $dto->playerId);

        $captainEntry->setIsCaptain(false);
        $newCaptainEntry->setIsCaptain(true);

        $this->entityManager->flush();

        $this->invalidateTeamCache($team);
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws NonUniqueResultException
     */
    public function updateTeam(Player $captain, UpdateTeamRequestDTO $dto): void
    {
        [$team] = $this->resolveTeamAndSeason($captain);

        $name = trim($dto->name);
        $town = $this->townRepository->find($dto->townId)
            ?? throw new LogicException('team_management.error.town_not_found');

        $duplicate = $this->teamRepository->findByNameAndTown($name, $town);
        if ($duplicate !== null && $duplicate->getId() !== $team->getId()) {
            throw new LogicException('team_management.error.name_taken');
        }

        $team->setName($name);
        $team->setTown($town);

        $this->entityManager->flush();

        $this->invalidateTeamCache($team);
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws NonUniqueResultException
     */
    public function leaveTeam(Player $player): void
    {
        $season = $this->seasonRepository->findCurrent()
            ?? throw new LogicException('team_management.error.no_season');

        $captainEntry = $this->teamPlayerRepository->findCaptainEntry($player, $season);
        if ($captainEntry !== null) {
            throw new LogicException('team_management.error.captain_cannot_leave');
        }

        $currentPlayers = $this->teamPlayerRepository->findByTeamAndSeason(
            $this->resolvePlayerTeam($player, $season),
            $season,
        );

        $targetEntry = null;
        foreach ($currentPlayers as $teamPlayer) {
            if ($teamPlayer->getPlayer()->getId() === $player->getId()) {
                $targetEntry = $teamPlayer;
                break;
            }
        }

        if ($targetEntry === null) {
            throw new LogicException('team_management.error.player_not_in_team');
        }

        $team = $targetEntry->getTeam();

        $this->entityManager->remove($targetEntry);
        $this->entityManager->flush();

        $this->cache->invalidateTags([
            CacheTag::team($team->getId()),
            CacheTag::playerSquad($player->getId()),
        ]);
    }

    /**
     * @return array{Team, Season}
     *
     * @throws LogicException
     * @throws NonUniqueResultException
     */
    private function resolveTeamAndSeason(Player $captain): array
    {
        $season = $this->seasonRepository->findCurrent()
            ?? throw new LogicException('team_management.error.no_season');

        $captainEntry = $this->teamPlayerRepository->findCaptainEntry($captain, $season)
            ?? throw new LogicException('team_management.error.not_captain');

        return [$captainEntry->getTeam(), $season];
    }

    /**
     * @throws LogicException
     */
    private function findPlayerEntry(Team $team, Season $season, int $playerId): TeamPlayer
    {
        $currentPlayers = $this->teamPlayerRepository->findByTeamAndSeason($team, $season);

        foreach ($currentPlayers as $teamPlayer) {
            if ($teamPlayer->getPlayer()->getId() === $playerId) {
                return $teamPlayer;
            }
        }

        throw new LogicException('team_management.error.player_not_in_team');
    }

    /**
     * @throws InvalidArgumentException
     */
    private function invalidateTeamCache(Team $team): void
    {
        $this->cache->invalidateTags([
            CacheTag::team($team->getId()),
        ]);
    }

    /**
     * @throws LogicException
     */
    private function resolvePlayerTeam(Player $player, Season $season): Team
    {
        return $this->resolvePlayerTeamOrNull($player, $season)
            ?? throw new LogicException('team_management.error.player_not_in_team');
    }

    private function resolvePlayerTeamOrNull(Player $player, Season $season): ?Team
    {
        $entries = $this->teamPlayerRepository->findBy([
            'player' => $player,
            'season' => $season,
        ]);

        if ($entries === []) {
            return null;
        }

        return $entries[0]->getTeam();
    }

    /**
     * @param list<int> $playerIds
     * @return list<TeamManagementPlayerDTO>
     */
    private function mapPlayerIds(array $playerIds): array
    {
        if ($playerIds === []) {
            return [];
        }

        $players = $this->playerRepository->findByIdsWithUser($playerIds);

        return $this->mapper->mapMultiple($players, TeamManagementPlayerDTO::class);
    }
}
