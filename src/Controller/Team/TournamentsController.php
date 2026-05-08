<?php

declare(strict_types=1);

namespace App\Controller\Team;

use App\Entity\Team;
use App\Service\TeamTournamentService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Helper\PageResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/team/{id}/tournaments', name: 'team_tournaments', requirements: ['id' => '\d+'], methods: ['GET'])]
class TournamentsController extends AbstractController
{
    public function __invoke(
        #[MapEntity(expr: 'repository.findWithTown(id)')] Team $team,
        Request $request,
        TeamTournamentService $tournamentService,
    ): Response {
        $page = PageResolver::resolve($request);

        return $this->render('team/_tournaments.html.twig', [
            'teamId' => $team->getId(),
            'tournaments' => $tournamentService->getTournaments($team, $page),
            'page' => $page,
            'lastPage' => $tournamentService->getLastPageNumber($team),
        ]);
    }
}
