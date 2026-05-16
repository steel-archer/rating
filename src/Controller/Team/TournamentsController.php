<?php

declare(strict_types=1);

namespace App\Controller\Team;

use App\DTO\Request\PageRequestDTO;
use App\Entity\Team;
use App\Service\TeamTournamentService;
use Doctrine\DBAL\Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/team/{id}/tournaments', name: 'team_tournaments', requirements: ['id' => '\d+'], methods: ['GET'])]
class TournamentsController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function __invoke(
        #[MapEntity(expr: 'repository.findWithTown(id)')] Team $team,
        TeamTournamentService $tournamentService,
        #[MapQueryString] PageRequestDTO $dto = new PageRequestDTO(),
    ): Response {
        return $this->render('team/_tournaments.html.twig', [
            'teamId' => $team->getId(),
            'tournaments' => $tournamentService->getTournaments($team, $dto->page),
            'page' => $dto->page,
            'lastPage' => $tournamentService->getLastPageNumber($team),
        ]);
    }
}
