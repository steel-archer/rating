<?php

declare(strict_types=1);

namespace App\Controller\My\Tournament;

use App\DTO\Request\Tournament\My\EditRequestDTO;
use App\Entity\Tournament;
use App\Security\TournamentOrganizerVoter;
use App\Service\TournamentManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/tournaments/{id}', name: 'my_tournament_update', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
class UpdateController extends AbstractController
{
    public function __invoke(
        Tournament $tournament,
        #[MapRequestPayload] EditRequestDTO $dto,
        TournamentManagementService $service,
    ): JsonResponse {
        if (!$this->isGranted(TournamentOrganizerVoter::EDIT, $tournament)) {
            return $this->json(['error' => 'common.not_found'], 404);
        }

        try {
            $service->update($tournament, $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        $this->addFlash('success', 'tournament.saved');

        return $this->json(['success' => true]);
    }
}
