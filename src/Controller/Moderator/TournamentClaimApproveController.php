<?php

namespace App\Controller\Moderator;

use App\Repository\TournamentRepository;
use App\Service\TournamentManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/moderator/tournaments/{id}/approve', name: 'moderator_tournament_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_MODERATOR')]
#[IsCsrfTokenValid(new Expression("'tournament_moderate_' ~ args['id']"))]
final class TournamentClaimApproveController extends AbstractController
{
    public function __invoke(
        int $id,
        TournamentRepository $tournamentRepository,
        TournamentManagementService $service,
    ): Response {
        $tournament = $tournamentRepository->find($id);
        if ($tournament === null) {
            throw $this->createNotFoundException();
        }

        $service->approve($tournament);

        return $this->redirectToRoute('moderator_tournaments');
    }
}
