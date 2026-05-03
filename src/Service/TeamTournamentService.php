<?php

namespace App\Service;

use App\DTO\Response\Team\TournamentEntryDTO;
use App\Entity\Team;
use App\Entity\TournamentSessionTeam;
use App\Helper\SessionTeamPlayerGrouper;
use App\Mapping\Mapper;
use App\Repository\TeamPlayerRepository;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Repository\TournamentSessionTeamRepository;
use Doctrine\DBAL\Exception as DbalException;

final readonly class TeamTournamentService
{
    private const int PER_PAGE = 50;

    public function __construct(
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
        private TeamPlayerRepository $teamPlayerRepository,
        private Mapper $mapper,
    ) {
    }

    /**
     * @throws DbalException
     * @return list<TournamentEntryDTO>
     */
    public function getTournaments(Team $team, int $page): array
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

    public function getLastPageNumber(Team $team): int
    {
        $total = $this->sessionTeamRepository->countByTeam($team);

        return max(1, (int) ceil($total / self::PER_PAGE));
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
