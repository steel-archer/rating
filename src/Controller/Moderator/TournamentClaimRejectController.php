<?php

namespace App\Controller\Moderator;

use App\DTO\Request\Tournament\Moderation\RejectRequestDTO;
use App\Repository\TournamentRepository;
use App\Service\TournamentManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/moderator/tournaments/{id}/reject', name: 'moderator_tournament_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_MODERATOR')]
#[IsCsrfTokenValid(new Expression("'tournament_moderate_' ~ args['id']"))]
final class TournamentClaimRejectController extends AbstractController
{
    public function __invoke(
        int $id,
        #[MapRequestPayload] RejectRequestDTO $dto,
        TournamentRepository $tournamentRepository,
        TournamentManagementService $service,
    ): Response {
        $tournament = $tournamentRepository->find($id);
        if ($tournament === null) {
            throw $this->createNotFoundException();
        }

        try {
            $service->reject($tournament, $dto->comment);
        } catch (LogicException $ex) {
            $this->addFlash('error', $ex->getMessage());
        } catch (Throwable) {
            $this->addFlash('error', 'common.error');
        }

        return $this->redirectToRoute('moderator_tournaments');
    }
}
