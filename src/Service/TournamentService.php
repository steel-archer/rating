<?php

namespace App\Service;

use App\DTO\Response\TournamentDTO;
use App\Entity\Tournament;
use App\Mapping\TournamentMapping;
use App\Repository\TeamPlayerRepository;
use App\Repository\TournamentOfficialRepository;
use App\Repository\TournamentRepository;
use App\Repository\TournamentSessionRepository;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Repository\TournamentSessionTeamRepository;
use InvalidArgumentException;

final readonly class TournamentService
{
    public function __construct(
        private TournamentRepository $tournamentRepository,
        private TournamentOfficialRepository $officialRepository,
        private TournamentSessionRepository $sessionRepository,
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
        private TeamPlayerRepository $teamPlayerRepository,
    ) {
    }

    public function get(int $id): TournamentDTO
    {
        /** @var Tournament $tournament */
        $tournament = $this->tournamentRepository->find($id)
            ?? throw new InvalidArgumentException("Tournament #$id not found");

        $season = $tournament->getSeason();

        return TournamentMapping::toDTO(
            tournament: $tournament,
            officials: $this->officialRepository->findByTournament($tournament),
            sessions: $this->sessionRepository->findByTournamentWithVenue($tournament),
            sessionTeams: $this->sessionTeamRepository->findByTournamentWithTeam($tournament),
            sessionTeamPlayers: $this->sessionTeamPlayerRepository->findByTournamentWithPlayer($tournament),
            squadMap: $season ? $this->teamPlayerRepository->getSquadMapBySeason($season) : [],
        );
    }
}
