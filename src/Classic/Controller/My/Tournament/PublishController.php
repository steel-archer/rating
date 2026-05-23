<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\Tournament;

use App\Common\Attribute\RateLimited;
use App\Classic\Entity\Tournament;
use App\Classic\Security\TournamentOrganizerVoter;
use App\Classic\Service\TournamentManagementService;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/tournaments/{id}/publish', name: 'my_tournament_publish', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class PublishController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(
        Tournament $tournament,
        TournamentManagementService $service,
    ): JsonResponse {
        if (!$this->isGranted(TournamentOrganizerVoter::EDIT, $tournament)) {
            return $this->json(['error' => 'common.not_found'], 404);
        }

        try {
            $service->publish($tournament);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
