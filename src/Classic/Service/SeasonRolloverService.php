<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Classic\DTO\Response\Rollover\RolloverResultDTO;
use App\Classic\DTO\Response\Rollover\RolloverTeamResultDTO;
use App\Classic\Entity\Team;
use App\Classic\Entity\TeamPlayer;
use App\Classic\Entity\TeamPlayerTransfer;
use App\Classic\Enum\TeamPlayerTransferType;
use App\Classic\Repository\TeamPlayerRepository;
use App\Classic\Repository\TeamRepository;
use App\Classic\Repository\TournamentSessionTeamPlayerRepository;
use App\Common\Entity\Player;
use App\Common\Entity\Season;
use App\Common\Enum\CacheTag;
use App\Common\Repository\PlayerRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Transfers team squads from a finished season into the next one.
 *
 * A player is carried over only if they were both in the team's base squad
 * (TeamPlayer) and actually played at least one game for that team (recorded
 * via TournamentSessionTeamPlayer) during the source season. Teams with no
 * eligible players are skipped. Captaincy stays with the current captain when
 * they are carried over; otherwise it is reassigned to the player with the most
 * games (ties broken by the lowest player id).
 */
final readonly class SeasonRolloverService
{
    public function __construct(
        private TeamPlayerRepository $teamPlayerRepository,
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
        private TeamRepository $teamRepository,
        private PlayerRepository $playerRepository,
        private EntityManagerInterface $entityManager,
        private TagAwareCacheInterface $cache,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function rollover(Season $source, Season $target, bool $dryRun): RolloverResultDTO
    {
        $joinDate = $target->getStartedAt()
            ?? throw new LogicException('season_rollover.error.target_season_without_start_date');

        $squadMap = $this->teamPlayerRepository->getSquadMapBySeason($source);
        $gamesByTeam = $this->sessionTeamPlayerRepository->countPlayerGamesByTeamForSeason($source);

        // A not-yet-persisted target (dry run) has no existing squad to reconcile against.
        $existingTargetSquad = $target->getId() === null
            ? []
            : $this->teamPlayerRepository->getSquadMapBySeason($target);

        // Batch-load every team and player up front to avoid N+1 queries in the loop.
        $teamsById = $this->loadTeamsById(array_keys($squadMap));
        $playersById = $this->loadPlayersById($this->collectPlayerIds($squadMap));

        $teamResults = [];
        $skippedTeamCount = 0;

        foreach ($squadMap as $teamId => $squad) {
            $gamesByPlayer = $gamesByTeam[$teamId] ?? [];

            // Carry over only base-squad players who actually played for the team.
            $eligiblePlayerIds = array_values(
                array_filter(
                    $squad['playerIds'],
                    static fn(int $playerId): bool => isset($gamesByPlayer[$playerId]),
                ),
            );

            if ($eligiblePlayerIds === []) {
                $skippedTeamCount++;
                continue;
            }

            // Rollover never touches a team that already has a squad in the target
            // season. This keeps a re-run from duplicating transfers or leaving a
            // partially-populated squad without a captain.
            if (($existingTargetSquad[$teamId]['playerIds'] ?? []) !== []) {
                $skippedTeamCount++;
                continue;
            }

            $team = $teamsById[$teamId] ?? null;
            if ($team === null) {
                // TeamPlayer references a team that no longer exists: a data anomaly.
                $this->logger->warning(
                    'Season rollover: skipping team not found in database.',
                    ['teamId' => $teamId, 'sourceSeasonId' => $source->getId()],
                );
                $skippedTeamCount++;
                continue;
            }

            $captainId = $this->resolveCaptainId($eligiblePlayerIds, $squad['captainId'], $gamesByPlayer);
            $captaincyReassigned = $captainId !== $squad['captainId'];

            $captainName = '';
            $transferredCount = 0;

            foreach ($eligiblePlayerIds as $playerId) {
                $player = $playersById[$playerId] ?? null;
                if ($player === null) {
                    // TeamPlayer references a player that no longer exists: a data anomaly.
                    $this->logger->warning(
                        'Season rollover: skipping player not found in database.',
                        ['playerId' => $playerId, 'teamId' => $teamId],
                    );
                    continue;
                }

                if ($playerId === $captainId) {
                    $captainName = $player->getFullName();
                }

                if (!$dryRun) {
                    $this->createTeamPlayer($team, $player, $target, $playerId === $captainId);
                    $this->recordJoin($player, $team, $target, $joinDate);
                }

                $transferredCount++;
            }

            $teamResults[] = new RolloverTeamResultDTO(
                teamId: $teamId,
                teamName: $team->getName(),
                transferredPlayerCount: $transferredCount,
                captainId: $captainId,
                captainName: $captainName,
                captaincyReassigned: $captaincyReassigned,
            );
        }

        if (!$dryRun) {
            $this->entityManager->flush();
            $this->invalidateCache($teamResults);
        }

        return new RolloverResultDTO(
            sourceSeasonName: $source->getName(),
            targetSeasonName: $target->getName(),
            dryRun: $dryRun,
            skippedTeamCount: $skippedTeamCount,
            teams: $teamResults,
        );
    }

    /**
     * @param array<int, array{playerIds: list<int>, captainId: int|null}> $squadMap
     * @return list<int>
     */
    private function collectPlayerIds(array $squadMap): array
    {
        $playerIds = [];
        foreach ($squadMap as $squad) {
            foreach ($squad['playerIds'] as $playerId) {
                $playerIds[$playerId] = true;
            }
        }

        return array_keys($playerIds);
    }

    /**
     * @param list<int> $teamIds
     * @return array<int, Team> teamId => team
     */
    private function loadTeamsById(array $teamIds): array
    {
        $teamsById = [];
        foreach ($this->teamRepository->findByIdsWithTown($teamIds) as $team) {
            $teamsById[$team->getId()] = $team;
        }

        return $teamsById;
    }

    /**
     * @param list<int> $playerIds
     * @return array<int, Player> playerId => player
     */
    private function loadPlayersById(array $playerIds): array
    {
        $playersById = [];
        foreach ($this->playerRepository->findByIdsWithUser($playerIds) as $player) {
            $playersById[$player->getId()] = $player;
        }

        return $playersById;
    }

    /**
     * Keeps the current captain if carried over; otherwise picks the player with
     * the most games, breaking ties by the lowest player id.
     *
     * @param list<int> $eligiblePlayerIds
     * @param array<int, int> $gamesByPlayer playerId => gameCount
     */
    private function resolveCaptainId(array $eligiblePlayerIds, ?int $currentCaptainId, array $gamesByPlayer): int
    {
        if ($currentCaptainId !== null && in_array($currentCaptainId, $eligiblePlayerIds, true)) {
            return $currentCaptainId;
        }

        $bestPlayerId = $eligiblePlayerIds[0];
        $bestGames = $gamesByPlayer[$bestPlayerId] ?? 0;

        foreach ($eligiblePlayerIds as $playerId) {
            $games = $gamesByPlayer[$playerId] ?? 0;
            if ($games > $bestGames || ($games === $bestGames && $playerId < $bestPlayerId)) {
                $bestPlayerId = $playerId;
                $bestGames = $games;
            }
        }

        return $bestPlayerId;
    }

    private function createTeamPlayer(Team $team, Player $player, Season $season, bool $isCaptain): void
    {
        $teamPlayer = new TeamPlayer();
        $teamPlayer->setTeam($team);
        $teamPlayer->setPlayer($player);
        $teamPlayer->setSeason($season);
        $teamPlayer->setIsCaptain($isCaptain);

        $this->entityManager->persist($teamPlayer);
    }

    private function recordJoin(Player $player, Team $team, Season $season, DateTimeImmutable $date): void
    {
        $transfer = new TeamPlayerTransfer();
        $transfer->setPlayer($player);
        $transfer->setTeam($team);
        $transfer->setSeason($season);
        $transfer->setType(TeamPlayerTransferType::Joined);
        $transfer->setDate($date);

        $this->entityManager->persist($transfer);
    }

    /**
     * @param list<RolloverTeamResultDTO> $teamResults
     *
     * @throws InvalidArgumentException
     */
    private function invalidateCache(array $teamResults): void
    {
        $tags = [];
        foreach ($teamResults as $result) {
            $tags[] = CacheTag::team($result->teamId);
        }

        if ($tags !== []) {
            $this->cache->invalidateTags($tags);
        }
    }
}
