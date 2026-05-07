<?php

declare(strict_types=1);

namespace App\Controller\Tournament;

use App\Entity\Tournament;
use App\Repository\TournamentSessionRepository;
use App\Repository\TournamentSessionTeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Helper\PageResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/tournament/{id}/sessions/list', name: 'tournament_sessions_list', requirements: ['id' => '\d+'], methods: ['GET'])]
class SessionsListController extends AbstractController
{
    public function __invoke(
        Tournament $tournament,
        Request $request,
        TournamentSessionRepository $sessionRepository,
        TournamentSessionTeamRepository $sessionTeamRepository,
    ): Response {
        try {
            $page = PageResolver::resolve($request);
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
        } catch (Throwable $ex) {
            throw new ServiceUnavailableHttpException(message: $ex->getMessage(), previous: $ex);
        }
    }
}
