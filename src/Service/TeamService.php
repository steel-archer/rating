<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\TeamDTO;
use App\Exception\EntityNotFoundException;
use App\Mapping\Mapper;
use App\Repository\TeamPlayerRepository;
use App\Repository\TeamRepository;
use App\Repository\TournamentSessionTeamRepository;

class TeamService
{
    public function __construct(
        private TeamRepository $teamRepository,
        private TeamPlayerRepository $teamPlayerRepository,
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private Mapper $mapper,
    ) {
    }

    /**
     * @throws EntityNotFoundException
     */
    public function get(int $id): TeamDTO
    {
        $team = $this->teamRepository->findWithTown($id)
            ?? throw EntityNotFoundException::forId('Team', $id);

        /** @var TeamDTO */
        return $this->mapper->map($team, TeamDTO::class, [
            'teamPlayers' => $this->teamPlayerRepository->findByTeamWithPlayerAndSeason($team),
            'tournamentCount' => $this->sessionTeamRepository->countByTeam($team),
        ]);
    }
}
