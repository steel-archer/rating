<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\Tournament;

use App\Common\Attribute\RateLimited;
use App\Classic\Entity\Tournament;
use App\Classic\Security\TournamentOrganizerVoter;
use App\Classic\Service\TournamentModerationService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/tournaments/{id}/submit', name: 'my_tournament_submit', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class SubmitController extends AbstractController
{
    public function __invoke(
        Tournament $tournament,
        TournamentModerationService $service,
    ): JsonResponse {
        if (!$this->isGranted(TournamentOrganizerVoter::EDIT, $tournament)) {
            return $this->json(['error' => 'common.not_found'], 404);
        }

        try {
            $service->submitForModeration($tournament);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
