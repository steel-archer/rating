<?php

namespace App\Controller\Team;

use App\Repository\TeamRepository;
use App\Service\TeamTournamentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/team/{id}/tournaments', name: 'team_tournaments', requirements: ['id' => '\d+'])]
final class TournamentsController extends AbstractController
{
    public function __invoke(int $id, Request $request, TeamRepository $teamRepository, TeamTournamentService $tournamentService): Response
    {
        $team = $teamRepository->findWithTown($id)
            ?? throw new NotFoundHttpException("Team #$id not found");

        $page = max(1, $request->query->getInt('page', 1));

        return $this->render('team/_tournaments.html.twig', [
            'team' => $team,
            'tournaments' => $tournamentService->getTournaments($team, $page),
            'page' => $page,
            'lastPage' => $tournamentService->getLastPage($team),
        ]);
    }
}
