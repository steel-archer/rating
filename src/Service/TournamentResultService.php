<?php

namespace App\Service;

use App\DTO\Response\Tournament\SessionTeamDTO;
use App\Entity\Tournament;
use App\Entity\TournamentSessionTeam;
use App\Entity\TournamentSessionTeamPlayer;
use App\Mapping\Mapper;
use App\Repository\TeamPlayerRepository;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Repository\TournamentSessionTeamRepository;
use Doctrine\DBAL\Exception as DbalException;

final readonly class TournamentResultService
{
    private const int PER_PAGE = 5;

    public function __construct(
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
        private TeamPlayerRepository $teamPlayerRepository,
        private Mapper $mapper,
    ) {
    }

    /**
     * @throws DbalException
     * @return list<SessionTeamDTO>
     */
    public function getResults(Tournament $tournament, int $page): array
    {
        $sessionTeams = $this->sessionTeamRepository->findByTournamentPaginated($tournament, $page, self::PER_PAGE);
        if ($sessionTeams === []) {
            return [];
        }

        $sessionTeamIds = array_map(static fn(TournamentSessionTeam $st) => $st->getId(), $sessionTeams);
        $playerMap = self::groupBySessionTeam(
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

    public function getLastPage(Tournament $tournament): int
    {
        $total = $this->sessionTeamRepository->countByTournament($tournament);

        return max(1, (int) ceil($total / self::PER_PAGE));
    }

    /**
     * @return array<int, list<TournamentSessionTeamPlayer>>
     */
    private static function groupBySessionTeam(array $players): array
    {
        $index = [];
        foreach ($players as $player) {
            $index[$player->getTournamentSessionTeam()->getId()][] = $player;
        }

        return $index;
    }
}
