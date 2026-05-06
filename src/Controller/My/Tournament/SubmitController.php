<?php

namespace App\Controller\My\Tournament;

use App\Entity\User;
use App\Repository\TournamentRepository;
use App\Service\TournamentManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/tournaments/{id}/submit', name: 'my_tournament_submit', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
#[IsCsrfTokenValid(new Expression("'tournament_submit_' ~ args['id']"))]
final class SubmitController extends AbstractController
{
    public function __invoke(
        int $id,
        TournamentRepository $tournamentRepository,
        TournamentManagementService $service,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $tournament = $tournamentRepository->find($id);

        if ($tournament === null || $tournament->getCreatedBy() !== $user) {
            throw $this->createNotFoundException();
        }

        $service->submitForModeration($tournament);
        $this->addFlash('success', 'tournament.submitted');

        return $this->redirectToRoute('my_tournament_edit', ['id' => $id]);
    }
}
