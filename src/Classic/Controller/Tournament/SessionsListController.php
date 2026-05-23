<?php

declare(strict_types=1);

namespace App\Classic\Controller\Tournament;

use App\Common\DTO\Request\PageRequestDTO;
use App\Classic\DTO\Response\Tournament\SessionDTO;
use App\Classic\DTO\Response\Tournament\TournamentContextDTO;
use App\Classic\Entity\Tournament;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\TournamentSessionRepository;
use App\Classic\Repository\TournamentSessionTeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournament/{id}/sessions/list', name: 'tournament_sessions_list', requirements: ['id' => '\d+'], methods: ['GET'])]
class SessionsListController extends AbstractController
{
    public function __invoke(
        Tournament $tournament,
        TournamentSessionRepository $sessionRepository,
        TournamentSessionTeamRepository $sessionTeamRepository,
        Mapper $mapper,
        #[MapQueryString] PageRequestDTO $dto = new PageRequestDTO(),
    ): Response {
        $sessions = $sessionRepository->findByTournamentPaginated($tournament, $dto->page);

        $teamCounts = $sessionTeamRepository->countBySessionIds(
            array_map(static fn($s) => $s->getId(), $sessions),
        );

        return $this->render('tournament/_sessions.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'sessions' => $mapper->mapMultiple($sessions, SessionDTO::class),
            'teamCounts' => $teamCounts,
            'page' => $dto->page,
            'lastPage' => $sessionRepository->getLastPageNumberByTournament($tournament),
        ]);
    }
}
