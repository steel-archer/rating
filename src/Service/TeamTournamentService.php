<?php

namespace App\Service;

use App\DTO\Response\Team\TournamentEntryDTO;
use App\DTO\Response\Team\TournamentPlayerDTO;
use App\Entity\Team;
use App\Entity\TournamentSessionTeam;
use App\Entity\TournamentSessionTeamPlayer;
use App\Mapping\Mapper;
use App\Repository\TeamPlayerRepository;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Repository\TournamentSessionTeamRepository;

final readonly class TeamTournamentService
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
     * @return list<TournamentEntryDTO>
     */
    public function getTournaments(Team $team, int $page): array
    {
        $sessionTeams = $this->sessionTeamRepository->findByTeamPaginated($team, $page, self::PER_PAGE);
        if ($sessionTeams === []) {
            return [];
        }

        $sessionTeamIds = array_map(static fn(TournamentSessionTeam $st) => $st->getId(), $sessionTeams);
        $allPlayers = $this->sessionTeamPlayerRepository->findBySessionTeamIds($sessionTeamIds);
        $playerMap = self::groupBySessionTeam($allPlayers);
        $places = $this->sessionTeamRepository->getPlacesInTournament($sessionTeamIds);

        // Build squadInfo per season from team's base squads
        $squadInfoBySeason = $this->buildSquadInfoBySeason($team);

        $result = [];
        foreach ($sessionTeams as $st) {
            $session = $st->getTournamentSession();
            $tournament = $session->getTournament();
            $seasonId = $tournament->getSeason()?->getId();
            $squadInfo = $squadInfoBySeason[$seasonId] ?? ['playerIds' => [], 'captainId' => null];

            $players = array_map(
                fn(TournamentSessionTeamPlayer $a) => $this->mapper->map($a, TournamentPlayerDTO::class, ['squadInfo' => $squadInfo]),
                $playerMap[$st->getId()] ?? [],
            );

            $result[] = new TournamentEntryDTO(
                tournamentId: $tournament->getId(),
                tournamentName: $tournament->getName(),
                playedAt: $session->getPlayedAt(),
                score: $st->getScore(),
                place: $places[$st->getId()] ?? null,
                players: $players,
            );
        }

        return $result;
    }

    public function getLastPage(Team $team): int
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
