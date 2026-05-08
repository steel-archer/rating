<?php

declare(strict_types=1);

namespace App\Controller\Moderator\Tournament;

use App\DTO\Request\Tournament\Moderation\RejectRequestDTO;
use App\Entity\Tournament;
use App\Service\TournamentModerationService;
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
        Tournament $tournament,
        #[MapRequestPayload] RejectRequestDTO $dto,
        TournamentModerationService $service,
    ): JsonResponse {
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
