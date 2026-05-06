<?php

namespace App\Service;

use App\DTO\Response\TournamentDTO;
use App\Exception\EntityNotFoundException;
use App\Mapping\Mapper;
use App\Repository\TournamentOfficialRepository;
use App\Repository\TournamentRepository;
use App\Repository\TournamentSessionRepository;
use App\Repository\TournamentSessionTeamRepository;

class TournamentService
{
    public function __construct(
        private TournamentRepository $tournamentRepository,
        private TournamentOfficialRepository $officialRepository,
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private TournamentSessionRepository $sessionRepository,
        private Mapper $mapper,
    ) {
    }

    /**
     * @throws EntityNotFoundException
     */
    public function get(int $id): TournamentDTO
    {
        $tournament = $this->tournamentRepository->findWithSeason($id)
            ?? throw EntityNotFoundException::forId('Tournament', $id);

        /** @var TournamentDTO */
        return $this->mapper->map($tournament, TournamentDTO::class, [
            'officials' => $this->officialRepository->findByTournament($tournament),
            'teamCount' => $this->sessionTeamRepository->countByTournament($tournament),
            'sessionCount' => $this->sessionRepository->countByTournament($tournament),
        ]);
    }
}
