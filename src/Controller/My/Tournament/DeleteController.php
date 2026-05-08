<?php

declare(strict_types=1);

namespace App\Controller\My\Tournament;

use App\Entity\Tournament;
use App\Security\TournamentOwnerVoter;
use App\Service\TournamentManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/my/tournaments/{id}/delete', name: 'my_tournament_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
class DeleteController extends AbstractController
{
    public function __invoke(
        Tournament $tournament,
        TournamentManagementService $service,
    ): JsonResponse {
        if (!$this->isGranted(TournamentOwnerVoter::EDIT, $tournament)) {
            return $this->json(['error' => 'common.not_found'], 404);
        }

        try {
            $service->delete($tournament);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        } catch (Throwable) {
            return $this->json(['error' => 'common.error'], 500);
        }

        return $this->json(['success' => true]);
    }
}
