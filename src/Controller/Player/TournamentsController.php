<?php

declare(strict_types=1);

namespace App\Controller\Player;

use App\Entity\Player;
use App\Service\PlayerTournamentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Helper\PageResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/player/{id}/tournaments', name: 'player_tournaments', requirements: ['id' => '\d+'], methods: ['GET'])]
class TournamentsController extends AbstractController
{
    public function __invoke(Player $player, Request $request, PlayerTournamentService $tournamentService): Response
    {
        $page = PageResolver::resolve($request);

        return $this->render('player/_tournaments.html.twig', [
            'playerId' => $player->getId(),
            'tournaments' => $tournamentService->getTournaments($player, $page),
            'page' => $page,
            'lastPage' => $tournamentService->getLastPageNumber($player),
        ]);
    }
}
