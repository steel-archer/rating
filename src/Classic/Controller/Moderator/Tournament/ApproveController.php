<?php

declare(strict_types=1);

namespace App\Classic\Controller\Moderator\Tournament;

use App\Common\Attribute\RateLimited;
use App\Classic\Entity\Tournament;
use App\Classic\Service\TournamentModerationService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderator/tournaments/{id}/approve', name: 'moderator_tournament_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('moderator')]
class ApproveController extends AbstractController
{
    public function __invoke(
        Tournament $tournament,
        TournamentModerationService $service,
    ): JsonResponse {
        try {
            $service->approve($tournament);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
