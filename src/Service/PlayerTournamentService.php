<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\Player\TournamentAppearanceDTO;
use App\Entity\Player;
use App\Entity\TournamentSessionTeamPlayer;
use App\Mapping\Mapper;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Repository\TournamentSessionTeamRepository;
use Doctrine\DBAL\Exception as DbalException;

class PlayerTournamentService
{
    private const int PER_PAGE = 50;

    public function __construct(
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private Mapper $mapper,
    ) {
    }

    /**
     * @throws DbalException
     * @return list<TournamentAppearanceDTO>
     */
    public function getTournaments(Player $player, int $page): array
    {
        $appearances = $this->sessionTeamPlayerRepository->findByPlayerPaginated($player, $page, self::PER_PAGE);
        if ($appearances === []) {
            return [];
        }

        $sessionTeamIds = array_map(
            static fn(TournamentSessionTeamPlayer $a) => $a->getTournamentSessionTeam()->getId(),
            $appearances,
        );
        $places = $this->sessionTeamRepository->getPlacesInTournament($sessionTeamIds);

        return array_map(
            fn(TournamentSessionTeamPlayer $a) => $this->mapper->map($a, TournamentAppearanceDTO::class, ['places' => $places]),
            $appearances,
        );
    }

    public function getLastPageNumber(Player $player): int
    {
        $total = $this->sessionTeamPlayerRepository->countByPlayer($player);

        return max(1, (int) ceil($total / self::PER_PAGE));
    }
}
