<?php

declare(strict_types=1);

namespace App\Controller\My\Tournament;

use App\Attribute\RateLimited;
use App\Entity\Tournament;
use App\Security\TournamentOrganizerVoter;
use App\Service\TournamentModerationService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/tournaments/{id}/submit', name: 'my_tournament_submit', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
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
