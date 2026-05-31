<?php

declare(strict_types=1);

namespace App\Classic\Controller\Tournament;

use App\Classic\DTO\Response\Tournament\TournamentContextDTO;
use App\Classic\Entity\Tournament;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use App\Classic\Service\SessionResultService;
use App\Classic\Service\TournamentDetailAccessService;
use App\Classic\Service\TournamentResultService;
use Doctrine\DBAL\Exception as DbalException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournament/{id}/detailed', name: 'tournament_detailed_results', requirements: ['id' => '\d+'], methods: ['GET'])]
class DetailedResultsController extends AbstractController
{
    /**
     * @throws DbalException
     * @throws InvalidArgumentException
     */
    public function __invoke(
        #[MapEntity(expr: 'repository.findWithSeason(id)')] Tournament $tournament,
        TournamentDetailAccessService $detailAccessService,
        TournamentResultService $resultService,
        SessionResultService $sessionResultService,
        Mapper $mapper,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$detailAccessService->canView($tournament, $user->getPlayer())) {
            throw $this->createNotFoundException();
        }

        $teams = $resultService->getAllResults($tournament);
        $breakdown = $sessionResultService->getTournamentAnswerBreakdown($tournament);

        return $this->render('tournament/detailed_results.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'toursCount' => $tournament->getToursCount() ?? 0,
            'questionsPerTour' => $tournament->getQuestionsPerTour() ?? 0,
            'teams' => $teams,
            'breakdown' => $breakdown,
        ]);
    }
}
