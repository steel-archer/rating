<?php

declare(strict_types=1);

namespace App\Classic\Helper;

use App\Classic\DTO\Response\Tournament\SessionTeamDTO;
use App\Common\Entity\Season;
use App\Classic\Entity\TournamentSessionTeam;
use App\Classic\Repository\TeamPlayerTransferRepository;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\TeamPlayerRepository;
use App\Classic\Repository\TournamentSessionTeamPlayerRepository;
use App\Classic\Repository\TournamentSessionTeamRepository;
use Doctrine\DBAL\Exception as DbalException;

class SessionTeamResultBuilder
{
    public function __construct(
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
        private TeamPlayerRepository $teamPlayerRepository,
        private TeamPlayerTransferRepository $transferRepository,
        private Mapper $mapper,
    ) {
    }

    /**
     * @param list<TournamentSessionTeam> $sessionTeams
     * @return list<SessionTeamDTO>
     * @throws DbalException
     */
    public function build(array $sessionTeams, ?Season $season): array
    {
        if ($sessionTeams === []) {
            return [];
        }

        $sessionTeamIds = array_map(static fn(TournamentSessionTeam $st) => $st->getId(), $sessionTeams);
        $playerMap = SessionTeamPlayerGrouper::group(
            $this->sessionTeamPlayerRepository->findBySessionTeamIds($sessionTeamIds),
        );
        $places = $this->sessionTeamRepository->getPlacesInTournament($sessionTeamIds);
        $sessionPlaces = $this->calculateSessionPlaces($sessionTeams);

        // Captain info from current state
        $squadMap = $season ? $this->teamPlayerRepository->getSquadMapBySeason($season) : [];

        // Batch-load all transfers for the season (single query)
        $transfers = $season ? $this->transferRepository->findAllBySeason($season) : [];

        $datesByTeam = [];
        foreach ($sessionTeams as $st) {
            $playedAt = $st->getTournamentSession()->getPlayedAt();
            $teamId = $st->getTeam()->getId();
            if ($season !== null && $playedAt !== null) {
                $datesByTeam[$teamId]['team'] = $st->getTeam();
                $datesByTeam[$teamId]['dates'][$playedAt->format('Y-m-d')] = $playedAt;
            }
        }

        // Deduplicate dates per team
        foreach ($datesByTeam as $teamId => $info) {
            $datesByTeam[$teamId]['dates'] = array_values($info['dates']);
        }

        /** @var array<int, array<string, list<int>>> teamId => (dateKey => playerIds) */
        $squadCache = $this->transferRepository->resolveSquadFromTransfers($transfers, $datesByTeam);

        return array_map(
            function (TournamentSessionTeam $st) use ($playerMap, $places, $squadMap, $sessionPlaces, $squadCache) {
                $playedAt = $st->getTournamentSession()->getPlayedAt();
                $teamId = $st->getTeam()->getId();

                $playerIds = [];
                if ($playedAt !== null) {
                    $dateKey = $playedAt->format('Y-m-d');
                    $playerIds = $squadCache[$teamId][$dateKey] ?? [];
                }

                $captainId = $squadMap[$teamId]['captainId'] ?? null;

                return $this->mapper->map($st, SessionTeamDTO::class, [
                    'place' => $places[$st->getId()] ?? null,
                    'sessionPlace' => $sessionPlaces[$st->getId()] ?? null,
                    'players' => $playerMap[$st->getId()] ?? [],
                    'squadInfo' => ['playerIds' => $playerIds, 'captainId' => $captainId],
                ]);
            },
            $sessionTeams,
        );
    }

    /**
     * @param list<TournamentSessionTeam> $sessionTeams
     * @return array<int, float> sessionTeamId => place within session
     */
    private function calculateSessionPlaces(array $sessionTeams): array
    {
        $scores = array_map(
            static fn(TournamentSessionTeam $st) => $st->getScore(),
            $sessionTeams,
        );

        rsort($scores);
        $ranks = FractionalRanking::rank($scores);

        $result = [];
        foreach ($sessionTeams as $sessionTeam) {
            $result[$sessionTeam->getId()] = $ranks[$sessionTeam->getScore()] ?? 0;
        }

        return $result;
    }
}
