<?php

namespace App\Controller\My;

use App\DTO\Request\Tournament\My\EditRequestDTO;
use App\Entity\User;
use App\Repository\TournamentRepository;
use App\Service\TournamentManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/my/tournaments/{id}', name: 'my_tournament_update', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
final class TournamentUpdateController extends AbstractController
{
    public function __invoke(
        int $id,
        #[MapRequestPayload] EditRequestDTO $dto,
        TournamentRepository $tournamentRepository,
        TournamentManagementService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $tournament = $tournamentRepository->find($id);

        if ($tournament === null || $tournament->getCreatedBy() !== $user) {
            return $this->json(['error' => 'Not found'], 404);
        }

        try {
            $service->update($tournament, $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        } catch (Throwable) {
            return $this->json(['error' => 'common.error'], 500);
        }

        $this->addFlash('success', 'tournament.saved');

        return $this->json(['success' => true]);
    }
}
