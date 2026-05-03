<?php

namespace App\Service;

use App\DTO\Response\PlayerDTO;
use App\Exception\EntityNotFoundException;
use App\Mapping\Mapper;
use App\Repository\PlayerRepository;
use App\Repository\TeamPlayerRepository;
use App\Repository\TournamentSessionTeamPlayerRepository;

final readonly class PlayerService
{
    public function __construct(
        private PlayerRepository $playerRepository,
        private TeamPlayerRepository $teamPlayerRepository,
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
        private Mapper $mapper,
    ) {
    }

    /**
     * @throws EntityNotFoundException
     */
    public function get(int $id): PlayerDTO
    {
        $player = $this->playerRepository->findWithTown($id)
            ?? throw EntityNotFoundException::forId('Player', $id);

        /** @var PlayerDTO */
        return $this->mapper->map($player, PlayerDTO::class, [
            'squads' => $this->teamPlayerRepository->findByPlayerWithTeamAndSeason($player),
            'tournamentCount' => $this->sessionTeamPlayerRepository->countByPlayer($player),
        ]);
    }
}
