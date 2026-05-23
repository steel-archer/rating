<?php

declare(strict_types=1);

namespace App\Classic\Helper;

use App\Classic\DTO\Response\Tournament\SessionTeamDTO;
use App\Common\Entity\Season;
use App\Classic\Entity\TournamentSessionTeam;
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
