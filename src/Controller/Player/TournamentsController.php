<?php

declare(strict_types=1);

namespace App\Controller\Player;

use App\DTO\Request\PageRequestDTO;
use App\Entity\Player;
use App\Service\PlayerTournamentService;
use Doctrine\DBAL\Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/player/{id}/tournaments', name: 'player_tournaments', requirements: ['id' => '\d+'], methods: ['GET'])]
class TournamentsController extends AbstractController
{
    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function __invoke(
        Player $player,
        PlayerTournamentService $tournamentService,
        #[MapQueryString] PageRequestDTO $dto = new PageRequestDTO(),
    ): Response {
        return $this->render('player/_tournaments.html.twig', [
            'playerId' => $player->getId(),
            'tournaments' => $tournamentService->getTournaments($player, $dto->page),
            'page' => $dto->page,
            'lastPage' => $tournamentService->getLastPageNumber($player),
        ]);
    }
}
