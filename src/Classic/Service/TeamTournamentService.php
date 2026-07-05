<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Classic\DTO\Response\Team\TournamentEntryDTO;
use App\Classic\Entity\Team;
use App\Classic\Entity\TournamentSessionTeam;
use App\Common\Enum\CacheTag;
use App\Classic\Helper\SessionTeamPlayerGrouper;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\TeamPlayerRepository;
use App\Classic\Repository\TeamPlayerTransferRepository;
use App\Classic\Repository\TournamentSessionTeamPlayerRepository;
use App\Classic\Repository\TournamentSessionTeamRepository;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class TeamTournamentService
{
    private const int PER_PAGE = 50;

    public function __construct(
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
        private TeamPlayerRepository $teamPlayerRepository,
        private TeamPlayerTransferRepository $transferRepository,
        private Mapper $mapper,
        private TagAwareCacheInterface $cache,
    ) {
    }

    /**
     * @throws DbalException
     * @throws InvalidArgumentException
     * @return list<TournamentEntryDTO>
     */
    public function getTournaments(Team $team, int $page): array
    {
        $teamId = $team->getId();
        $cacheKey = "team_tournaments_{$teamId}_page_$page";

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($team, $page, $teamId) {
            $item->tag([CacheTag::team($teamId)]);
            $item->expiresAfter(3600);

            return $this->buildTournaments($team, $page);
        });
    }

    /**
     * @throws InvalidArgumentException
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getLastPageNumber(Team $team): int
    {
        $teamId = $team->getId();
        $cacheKey = "team_tournaments_last_page_$teamId";

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($team, $teamId) {
            $item->tag([CacheTag::team($teamId)]);
            $item->expiresAfter(3600);

            $total = $this->sessionTeamRepository->countByTeam($team);

            return max(1, (int) ceil($total / self::PER_PAGE));
        });
    }

    /**
     * @return list<TournamentEntryDTO>
     * @throws DbalException
     */
    private function buildTournaments(Team $team, int $page): array
    {
        $sessionTeams = $this->sessionTeamRepository->findByTeamPaginated($team, $page, self::PER_PAGE);
        if ($sessionTeams === []) {
            return [];
        }

        $sessionTeamIds = array_map(static fn(TournamentSessionTeam $st) => $st->getId(), $sessionTeams);
        $playerMap = SessionTeamPlayerGrouper::group(
            $this->sessionTeamPlayerRepository->findBySessionTeamIds($sessionTeamIds),
        );
        $places = $this->sessionTeamRepository->getPlacesInTournament($sessionTeamIds);

        // Collect unique dates and seasons, batch-load transfers
        $datesBySeason = [];
        foreach ($sessionTeams as $st) {
            $session = $st->getTournamentSession();
            $season = $session->getTournament()->getSeason();
            $playedAt = $session->getPlayedAt();
            if ($season !== null && $playedAt !== null) {
                $datesBySeason[$season->getId()]['season'] = $season;
                $datesBySeason[$season->getId()]['dates'][$playedAt->format('Y-m-d')] = $playedAt;
            }
        }

        /** @var array<int, array<string, list<int>>> seasonId => (dateKey => playerIds) */
        $squadCache = array_map(function ($info) use ($team) {
            return $this->transferRepository->findPlayerIdsInTeamOnDates(
                $team,
                $info['season'],
                array_values($info['dates']),
            );
        }, $datesBySeason);

        // Captain info per season (single query per season)
        $captainCache = [];
        foreach ($datesBySeason as $seasonId => $info) {
            $squadMap = $this->teamPlayerRepository->getSquadMapBySeason($info['season']);
            $captainCache[$seasonId] = $squadMap[$team->getId()]['captainId'] ?? null;
        }

        return array_map(
            function (TournamentSessionTeam $st) use ($playerMap, $places, $squadCache, $captainCache) {
                $session = $st->getTournamentSession();
                $season = $session->getTournament()->getSeason();
                $playedAt = $session->getPlayedAt();

                $playerIds = [];
                $captainId = null;
                if ($season !== null && $playedAt !== null) {
                    $seasonId = $season->getId();
                    $dateKey = $playedAt->format('Y-m-d');
                    $playerIds = $squadCache[$seasonId][$dateKey] ?? [];
                    $captainId = $captainCache[$seasonId] ?? null;
                }

                return $this->mapper->map($st, TournamentEntryDTO::class, [
                    'place' => $places[$st->getId()] ?? null,
                    'players' => $playerMap[$st->getId()] ?? [],
                    'squadInfo' => ['playerIds' => $playerIds, 'captainId' => $captainId],
                ]);
            },
            $sessionTeams,
        );
    }
}
