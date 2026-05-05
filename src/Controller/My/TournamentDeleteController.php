<?php

namespace App\Controller\My;

use App\Entity\User;
use App\Repository\TournamentRepository;
use App\Service\TournamentManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/tournaments/{id}/delete', name: 'my_tournament_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_USER')]
#[IsCsrfTokenValid(new Expression("'tournament_delete_' ~ args['id']"))]
final class TournamentDeleteController extends AbstractController
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

        try {
            $service->delete($tournament);
            $this->addFlash('success', 'tournament.deleted');
        } catch (LogicException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('my_tournament_edit', ['id' => $id]);
        }

        return $this->redirectToRoute('my_tournaments');
    }
}
