<?php

declare(strict_types=1);

namespace App\Controller\My\Tournament;

use App\Attribute\RateLimited;
use App\Entity\Tournament;
use App\Security\TournamentOrganizerVoter;
use App\Service\TournamentManagementService;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/tournaments/{id}/delete', name: 'my_tournament_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class DeleteController extends AbstractController
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
            $service->delete($tournament);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
