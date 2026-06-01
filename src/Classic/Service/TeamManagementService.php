<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Classic\DTO\Request\TeamManagement\UpdateSquadRequestDTO;
use App\Classic\DTO\Request\TeamManagement\UpdateTeamRequestDTO;
use App\Classic\DTO\Response\My\TeamManagementDTO;
use App\Classic\DTO\Response\My\TeamManagementPlayerDTO;
use App\Common\Entity\Player;
use App\Common\Entity\Season;
use App\Classic\Entity\Team;
use App\Classic\Entity\TeamPlayer;
use App\Common\Enum\CacheTag;
use App\Common\Mapping\Mapper;
use App\Common\Repository\PlayerRepository;
use App\Common\Repository\SeasonRepository;
use App\Classic\Repository\TeamPlayerRepository;
use App\Classic\Repository\TeamRepository;
use App\Classic\Repository\TournamentSessionTeamPlayerRepository;
use App\Common\Repository\TownRepository;
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
    public function updateSquad(Player $captain, UpdateSquadRequestDTO $dto): void
    {
        [$team, $season] = $this->resolveTeamAndSeason($captain);

        $currentPlayers = $this->teamPlayerRepository->findByTeamAndSeason($team, $season);
        $currentPlayerIds = array_map(
            static fn(TeamPlayer $tp) => $tp->getPlayer()->getId(),
            $currentPlayers,
        );

        $removePlayerIds = array_unique($dto->removePlayerIds);
        $addPlayerIds = array_unique($dto->addPlayerIds);

        // Validate no overlap between add and remove
        if (array_intersect($addPlayerIds, $removePlayerIds) !== []) {
            throw new LogicException('team_management.error.player_not_in_team');
        }

        // Validate removals
        foreach ($removePlayerIds as $playerId) {
            if ($playerId === $captain->getId()) {
                throw new LogicException('team_management.error.cannot_remove_self');
            }
            if (!in_array($playerId, $currentPlayerIds, true)) {
                throw new LogicException('team_management.error.player_not_in_team');
            }
        }

        // Validate max players after changes
        $resultCount = count($currentPlayers) - count($removePlayerIds) + count($addPlayerIds);
        if ($resultCount > self::MAX_PLAYERS) {
            throw new LogicException('team_management.error.max_players');
        }

        // Remove players
        foreach ($currentPlayers as $teamPlayer) {
            if (in_array($teamPlayer->getPlayer()->getId(), $removePlayerIds, true)) {
                $this->entityManager->remove($teamPlayer);
            }
        }

        // Add players
        foreach ($addPlayerIds as $playerId) {
            $player = $this->playerRepository->find($playerId)
                ?? throw new LogicException('team_management.error.player_not_found');

            if (in_array($playerId, $currentPlayerIds, true)) {
                throw new LogicException(
                    'team_management.error.already_in_team:' . $player->getFullName(),
                );
            }

            $existingEntry = $this->teamPlayerRepository->findBy([
                'player' => $player,
                'season' => $season,
            ]);
            if ($existingEntry !== []) {
                throw new LogicException(
                    'team_management.error.player_in_another_team:' . $player->getFullName(),
                );
            }

            $teamPlayer = new TeamPlayer();
            $teamPlayer->setTeam($team);
            $teamPlayer->setPlayer($player);
            $teamPlayer->setSeason($season);

            $this->entityManager->persist($teamPlayer);
        }

        // Transfer captaincy
        if ($dto->newCaptainId !== null) {
            if ($dto->newCaptainId === $captain->getId()) {
                throw new LogicException('team_management.error.already_captain');
            }
            if (in_array($dto->newCaptainId, $removePlayerIds, true)) {
                throw new LogicException('team_management.error.player_not_in_team');
            }
            if (
                !in_array($dto->newCaptainId, $currentPlayerIds, true)
                && !in_array($dto->newCaptainId, $addPlayerIds, true)
            ) {
                throw new LogicException('team_management.error.player_not_in_team');
            }

            $captainEntry = $this->teamPlayerRepository->findCaptainEntry($captain, $season);
            $captainEntry->setIsCaptain(false);

            // New captain may be an existing or newly added player
            $this->entityManager->flush();
            $newCaptainEntry = $this->findPlayerEntry($team, $season, $dto->newCaptainId);
            $newCaptainEntry->setIsCaptain(true);
        }

        $this->entityManager->flush();

        $this->invalidateTeamCache($team);

        $affectedPlayerIds = array_merge($removePlayerIds, $addPlayerIds);
        $tags = array_map(
            static fn(int $id) => CacheTag::playerSquad($id),
            $affectedPlayerIds,
        );
        if ($tags !== []) {
            $this->cache->invalidateTags($tags);
        }
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
