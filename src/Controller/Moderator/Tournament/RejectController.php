<?php

namespace App\Controller\Moderator\Tournament;

use App\DTO\Request\Tournament\Moderation\RejectRequestDTO;
use App\Repository\TournamentRepository;
use App\Service\TournamentManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/moderator/tournaments/{id}/reject', name: 'moderator_tournament_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_MODERATOR')]
class RejectController extends AbstractController
{
    public function __invoke(
        int $id,
        #[MapRequestPayload] RejectRequestDTO $dto,
        TournamentRepository $tournamentRepository,
        TournamentManagementService $service,
    ): JsonResponse {
        $tournament = $tournamentRepository->find($id);
        if ($tournament === null) {
            return $this->json(['error' => 'common.not_found'], 404);
        }

        try {
            $service->reject($tournament, $dto->comment);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        } catch (Throwable) {
            return $this->json(['error' => 'common.error'], 500);
        }

        return $this->json(['success' => true]);
    }
}
