<?php

declare(strict_types=1);

namespace App\Controller\Team;

use App\Repository\TeamRepository;
use App\Service\TeamTournamentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Helper\PageResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/team/{id}/tournaments', name: 'team_tournaments', requirements: ['id' => '\d+'], methods: ['GET'])]
class TournamentsController extends AbstractController
{
    public function __invoke(int $id, Request $request, TeamRepository $teamRepository, TeamTournamentService $tournamentService): Response
    {
        try {
            $team = $teamRepository->findWithTown($id)
                ?? throw new NotFoundHttpException("Team #$id not found");

            $page = PageResolver::resolve($request);

            return $this->render('team/_tournaments.html.twig', [
                'teamId' => $team->getId(),
                'tournaments' => $tournamentService->getTournaments($team, $page),
                'page' => $page,
                'lastPage' => $tournamentService->getLastPageNumber($team),
            ]);
        } catch (NotFoundHttpException $ex) {
            throw $ex;
        } catch (Throwable $ex) {
            throw new ServiceUnavailableHttpException(message: $ex->getMessage(), previous: $ex);
        }
    }
}
