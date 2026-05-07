<?php

declare(strict_types=1);

namespace App\Controller\Moderator\Tournament;

use App\Repository\TournamentRepository;
use App\Service\TournamentModerationService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/moderator/tournaments/{id}/approve', name: 'moderator_tournament_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_MODERATOR')]
class ApproveController extends AbstractController
{
    public function __invoke(
        int $id,
        TournamentRepository $tournamentRepository,
        TournamentModerationService $service,
    ): JsonResponse {
        $tournament = $tournamentRepository->find($id);
        if ($tournament === null) {
            return $this->json(['error' => 'common.not_found'], 404);
        }

        try {
            $service->approve($tournament);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        } catch (Throwable) {
            return $this->json(['error' => 'common.error'], 500);
        }

        return $this->json(['success' => true]);
    }
}
