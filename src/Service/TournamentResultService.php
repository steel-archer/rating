<?php

namespace App\Service;

use App\DTO\Response\Tournament\SessionTeamDTO;
use App\DTO\Response\Tournament\SessionTeamPlayerDTO;
use App\Entity\Tournament;
use App\Entity\TournamentSessionTeam;
use App\Entity\TournamentSessionTeamPlayer;
use App\Mapping\Mapper;
use App\Repository\TeamPlayerRepository;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Repository\TournamentSessionTeamRepository;

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
     * @return list<SessionTeamDTO>
     */
    public function getResults(Tournament $tournament, int $page): array
    {
        $sessionTeams = $this->sessionTeamRepository->findByTournamentPaginated($tournament, $page, self::PER_PAGE);
        if ($sessionTeams === []) {
            return [];
        }

        $sessionTeamIds = array_map(static fn(TournamentSessionTeam $st) => $st->getId(), $sessionTeams);
        $players = $this->sessionTeamPlayerRepository->findBySessionTeamIds($sessionTeamIds);
        $playerMap = self::groupBySessionTeam($players);

        $places = $this->sessionTeamRepository->getPlacesInTournament($sessionTeamIds);

        $season = $tournament->getSeason();
        $squadMap = $season ? $this->teamPlayerRepository->getSquadMapBySeason($season) : [];

        $result = [];
        foreach ($sessionTeams as $sessionTeam) {
            $teamId = $sessionTeam->getTeam()->getId();
            $squadInfo = $squadMap[$teamId] ?? ['playerIds' => [], 'captainId' => null];
            $teamPlayers = $playerMap[$sessionTeam->getId()] ?? [];

            $playerDTOs = array_map(
                fn(TournamentSessionTeamPlayer $p) => $this->mapper->map($p, SessionTeamPlayerDTO::class, ['squadInfo' => $squadInfo]),
                $teamPlayers,
            );

            $result[] = new SessionTeamDTO(
                teamId: $teamId,
                teamName: $sessionTeam->getTeam()->getName(),
                teamTownName: $sessionTeam->getTeam()->getTown()->getName(),
                score: $sessionTeam->getScore(),
                place: $places[$sessionTeam->getId()] ?? null,
                players: $playerDTOs,
            );
        }

        return $result;
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
