<?php

namespace App\Service;

use App\DTO\Response\TeamDTO;
use App\Exception\EntityNotFoundException;
use App\Mapping\Mapper;
use App\Repository\TeamPlayerRepository;
use App\Repository\TeamRepository;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Repository\TournamentSessionTeamRepository;
use Doctrine\DBAL\Exception;

final readonly class TeamService
{
    public function __construct(
        private TeamRepository $teamRepository,
        private TeamPlayerRepository $teamPlayerRepository,
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private Mapper $mapper,
    ) {
    }

    /**
     * @throws Exception
     */
    public function get(int $id): TeamDTO
    {
        $team = $this->teamRepository->findWithTown($id)
            ?? throw EntityNotFoundException::forId('Team', $id);

        $appearances = $this->sessionTeamPlayerRepository->findByTeamWithFullContext($team);

        $sessionTeamIds = array_map(
                static fn($a) => $a->getTournamentSessionTeam()->getId(),
                $appearances,
            )
                |> array_unique(...)
                |> array_values(...);

        /** @var TeamDTO */
        return $this->mapper->map($team, TeamDTO::class, [
            'teamPlayers' => $this->teamPlayerRepository->findByTeamWithPlayerAndSeason($team),
            'appearances' => $appearances,
            'places' => $this->sessionTeamRepository->getPlacesInTournament($sessionTeamIds),
        ]);
    }
}
