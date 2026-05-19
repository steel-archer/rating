<?php

declare(strict_types=1);

namespace App\Controller\Tournament;

use App\DTO\Response\My\DisputeDTO;
use App\DTO\Response\Tournament\TournamentContextDTO;
use App\Entity\Tournament;
use App\Entity\User;
use App\Enum\TournamentStatus;
use App\Mapping\Mapper;
use App\Repository\TournamentSessionTeamAnswerRepository;
use App\Service\TournamentDisputeAccessService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournament/{id}/disputes', name: 'tournament_disputes', requirements: ['id' => '\d+'], methods: ['GET'])]
class DisputesController extends AbstractController
{
    public function __invoke(
        #[MapEntity(expr: 'repository.findWithSeason(id)')] Tournament $tournament,
        TournamentSessionTeamAnswerRepository $answerRepository,
        TournamentDisputeAccessService $accessService,
        Mapper $mapper,
    ): Response {
        if ($tournament->getStatus() !== TournamentStatus::Published) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        if (!$accessService->canView($tournament, $player)) {
            throw $this->createAccessDeniedException();
        }

        $disputes = $answerRepository->findSubmittedDisputesByTournament($tournament);

        return $this->render('tournament/disputes.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'disputes' => $mapper->mapMultiple($disputes, DisputeDTO::class),
        ]);
    }
}
