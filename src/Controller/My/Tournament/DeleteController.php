<?php

namespace App\Controller\My\Tournament;

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
use Throwable;

#[Route('/my/tournaments/{id}/delete', name: 'my_tournament_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
#[IsCsrfTokenValid(new Expression("'tournament_delete_' ~ args['id']"))]
final class DeleteController extends AbstractController
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
        } catch (LogicException $ex) {
            $this->addFlash('error', $ex->getMessage());

            return $this->redirectToRoute('my_tournament_edit', ['id' => $id]);
        } catch (Throwable) {
            $this->addFlash('error', 'common.error');

            return $this->redirectToRoute('my_tournament_edit', ['id' => $id]);
        }

        return $this->redirectToRoute('my_tournaments');
    }
}
