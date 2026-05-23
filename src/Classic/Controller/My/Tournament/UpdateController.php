<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\Tournament;

use App\Common\Attribute\RateLimited;
use App\Classic\DTO\Request\Tournament\My\EditRequestDTO;
use App\Classic\Entity\Tournament;
use App\Classic\Security\TournamentOrganizerVoter;
use App\Classic\Service\TournamentManagementService;
use DateMalformedStringException;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/tournaments/{id}', name: 'my_tournament_update', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class UpdateController extends AbstractController
{
    /**
     * @throws DateMalformedStringException
     * @throws InvalidArgumentException
     */
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
