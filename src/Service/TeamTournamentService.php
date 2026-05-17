<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\Team\TournamentEntryDTO;
use App\Entity\Team;
use App\Entity\TournamentSessionTeam;
use App\Enum\CacheTag;
use App\Helper\SessionTeamPlayerGrouper;
use App\Mapping\Mapper;
use App\Repository\TeamPlayerRepository;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Repository\TournamentSessionTeamRepository;
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
        $squadInfoBySeason = $this->buildSquadInfoBySeason($team);

        return array_map(
            function (TournamentSessionTeam $st) use ($playerMap, $places, $squadInfoBySeason) {
                $seasonId = $st->getTournamentSession()->getTournament()->getSeason()?->getId();

                return $this->mapper->map($st, TournamentEntryDTO::class, [
                    'place' => $places[$st->getId()] ?? null,
                    'players' => $playerMap[$st->getId()] ?? [],
                    'squadInfo' => $squadInfoBySeason[$seasonId] ?? ['playerIds' => [], 'captainId' => null],
                ]);
            },
            $sessionTeams,
        );
    }

    /**
     * @return array<int, array{playerIds: list<int>, captainId: int|null}> seasonId => squadInfo
     */
    private function buildSquadInfoBySeason(Team $team): array
    {
        $teamPlayers = $this->teamPlayerRepository->findByTeamWithPlayerAndSeason($team);
        $result = [];

        foreach ($teamPlayers as $tp) {
            $seasonId = $tp->getSeason()->getId();
            if (!isset($result[$seasonId])) {
                $result[$seasonId] = ['playerIds' => [], 'captainId' => null];
            }
            $result[$seasonId]['playerIds'][] = $tp->getPlayer()->getId();
            if ($tp->isCaptain()) {
                $result[$seasonId]['captainId'] = $tp->getPlayer()->getId();
            }
        }

        return $result;
    }
}
