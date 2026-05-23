<?php

declare(strict_types=1);

namespace App\Classic\Controller\Moderator\Tournament;

use App\Common\Attribute\RateLimited;
use App\Classic\DTO\Request\Tournament\Moderation\RejectRequestDTO;
use App\Classic\Entity\Tournament;
use App\Classic\Service\TournamentModerationService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderator/tournaments/{id}/reject', name: 'moderator_tournament_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('moderator')]
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
        }

        return $this->json(['success' => true]);
    }
}
