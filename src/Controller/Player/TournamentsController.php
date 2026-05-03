<?php

namespace App\Controller\Player;

use App\Repository\PlayerRepository;
use App\Service\PlayerTournamentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Helper\PageResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/player/{id}/tournaments', name: 'player_tournaments', requirements: ['id' => '\d+'], methods: ['GET'])]
final class TournamentsController extends AbstractController
{
    public function __invoke(int $id, Request $request, PlayerRepository $playerRepository, PlayerTournamentService $tournamentService): Response
    {
        try {
            $player = $playerRepository->find($id)
                ?? throw new NotFoundHttpException("Player #$id not found");

            $page = PageResolver::resolve($request);

            return $this->render('player/_tournaments.html.twig', [
                'player' => $player,
                'tournaments' => $tournamentService->getTournaments($player, $page),
                'page' => $page,
                'lastPage' => $tournamentService->getLastPageNumber($player),
            ]);
        } catch (NotFoundHttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ServiceUnavailableHttpException(message: $exception->getMessage(), previous: $exception);
        }
    }
}
