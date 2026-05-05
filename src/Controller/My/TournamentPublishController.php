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

#[Route('/my/tournaments/{id}/publish', name: 'my_tournament_publish', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_USER')]
#[IsCsrfTokenValid(new Expression("'tournament_publish_' ~ args['id']"))]
final class TournamentPublishController extends AbstractController
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
            $service->publish($tournament);
            $this->addFlash('success', 'tournament.published');
        } catch (LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('my_tournament_edit', ['id' => $id]);
    }
}
