<?php

declare(strict_types=1);

namespace App\Classic\Controller\Tournament;

use App\Classic\DTO\Response\My\DisputeDTO;
use App\Classic\DTO\Response\Tournament\TournamentContextDTO;
use App\Classic\Entity\Tournament;
use App\Common\Entity\User;
use App\Classic\Enum\TournamentStatus;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\TournamentSessionTeamAnswerRepository;
use App\Classic\Service\TournamentDisputeAccessService;
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
