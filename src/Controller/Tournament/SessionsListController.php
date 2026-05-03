<?php

namespace App\Controller\Tournament;

use App\Repository\TournamentRepository;
use App\Repository\TournamentSessionRepository;
use App\Repository\TournamentSessionTeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/tournament/{id}/sessions/list', name: 'tournament_sessions_list', requirements: ['id' => '\d+'])]
final class SessionsListController extends AbstractController
{
    public function __invoke(
        int $id,
        Request $request,
        TournamentRepository $tournamentRepository,
        TournamentSessionRepository $sessionRepository,
        TournamentSessionTeamRepository $sessionTeamRepository,
    ): Response {
        try {
            $tournament = $tournamentRepository->find($id)
                ?? throw new NotFoundHttpException("Tournament #$id not found");

            $page = max(1, $request->query->getInt('page', 1));
            $sessions = $sessionRepository->findByTournamentPaginated($tournament, $page);

            $teamCounts = $sessionTeamRepository->countBySessionIds(
                array_map(static fn($s) => $s->getId(), $sessions),
            );

            return $this->render('tournament/_sessions.html.twig', [
                'tournament' => $tournament,
                'sessions' => $sessions,
                'teamCounts' => $teamCounts,
                'page' => $page,
                'lastPage' => $sessionRepository->getLastPageNumberByTournament($tournament),
            ]);
        } catch (NotFoundHttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ServiceUnavailableHttpException(message: $exception->getMessage(), previous: $exception);
        }
    }
}
