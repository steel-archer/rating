<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\Tournament\SessionTeamDTO;
use App\Entity\Tournament;
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

class TournamentResultService
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
     * @return list<SessionTeamDTO>
     */
    public function getResults(Tournament $tournament, int $page): array
    {
        $tournamentId = $tournament->getId();
        $cacheKey = "tournament_results_{$tournamentId}_page_{$page}";

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($tournament, $page, $tournamentId) {
            $item->tag([CacheTag::tournament($tournamentId)]);
            $item->expiresAfter(86400);

            return $this->buildResults($tournament, $page);
        });
    }

    /**
     * @throws InvalidArgumentException
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getLastPageNumber(Tournament $tournament): int
    {
        $tournamentId = $tournament->getId();
        $cacheKey = "tournament_results_last_page_{$tournamentId}";

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($tournament, $tournamentId) {
            $item->tag([CacheTag::tournament($tournamentId)]);
            $item->expiresAfter(86400);

            $total = $this->sessionTeamRepository->countByTournament($tournament);

            return max(1, (int) ceil($total / self::PER_PAGE));
        });
    }

    /**
     * @return list<SessionTeamDTO>
     */
    private function buildResults(Tournament $tournament, int $page): array
    {
        $sessionTeams = $this->sessionTeamRepository->findByTournamentPaginated($tournament, $page, self::PER_PAGE);
        if ($sessionTeams === []) {
            return [];
        }

        $sessionTeamIds = array_map(static fn(TournamentSessionTeam $st) => $st->getId(), $sessionTeams);
        $playerMap = SessionTeamPlayerGrouper::group(
            $this->sessionTeamPlayerRepository->findBySessionTeamIds($sessionTeamIds),
        );
        $places = $this->sessionTeamRepository->getPlacesInTournament($sessionTeamIds);

        $season = $tournament->getSeason();
        $squadMap = $season ? $this->teamPlayerRepository->getSquadMapBySeason($season) : [];

        return array_map(
            function (TournamentSessionTeam $st) use ($playerMap, $places, $squadMap) {
                $squadInfo = $squadMap[$st->getTeam()->getId()] ?? ['playerIds' => [], 'captainId' => null];

                return $this->mapper->map($st, SessionTeamDTO::class, [
                    'place' => $places[$st->getId()] ?? null,
                    'players' => $playerMap[$st->getId()] ?? [],
                    'squadInfo' => $squadInfo,
                ]);
            },
            $sessionTeams,
        );
    }
}
