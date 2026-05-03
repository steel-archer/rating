<?php

namespace App\Service;

use App\DTO\Response\PlayerDTO;
use App\Exception\EntityNotFoundException;
use App\Mapping\Mapper;
use App\Repository\PlayerRepository;
use App\Repository\TeamPlayerRepository;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Repository\TournamentSessionTeamRepository;
use Doctrine\DBAL\Exception;

final readonly class PlayerService
{
    public function __construct(
        private PlayerRepository $playerRepository,
        private TeamPlayerRepository $teamPlayerRepository,
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private Mapper $mapper,
    ) {
    }

    /**
     * @throws Exception
     */
    public function get(int $id): PlayerDTO
    {
        $player = $this->playerRepository->findWithTown($id)
            ?? throw EntityNotFoundException::forId('Player', $id);

        $appearances = $this->sessionTeamPlayerRepository->findByPlayerWithFullContext($player);

        $sessionTeamIds = array_map(
            static fn($appearance) => $appearance->getTournamentSessionTeam()->getId(),
            $appearances,
        );

        /** @var PlayerDTO */
        return $this->mapper->map($player, PlayerDTO::class, [
            'squads' => $this->teamPlayerRepository->findByPlayerWithTeamAndSeason($player),
            'appearances' => $appearances,
            'places' => $this->sessionTeamRepository->getPlacesInTournament($sessionTeamIds),
        ]);
    }
}
