<?php

namespace App\Controller\Player;

use App\Repository\PlayerRepository;
use App\Service\PlayerTournamentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/player/{id}/tournaments', name: 'player_tournaments', requirements: ['id' => '\d+'])]
final class TournamentsController extends AbstractController
{
    public function __invoke(int $id, Request $request, PlayerRepository $playerRepository, PlayerTournamentService $tournamentService): Response
    {
        $player = $playerRepository->find($id)
            ?? throw new NotFoundHttpException("Player #$id not found");

        $page = max(1, $request->query->getInt('page', 1));

        return $this->render('player/_tournaments.html.twig', [
            'player' => $player,
            'tournaments' => $tournamentService->getTournaments($player, $page),
            'page' => $page,
            'lastPage' => $tournamentService->getLastPage($player),
        ]);
    }
}
