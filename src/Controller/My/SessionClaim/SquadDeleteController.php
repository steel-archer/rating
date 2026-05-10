<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim;

use App\Entity\TournamentSessionTeam;
use App\Entity\User;
use App\Service\SessionSquadService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/session-teams/{id}/delete', name: 'my_session_team_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
class SquadDeleteController extends AbstractController
{
    public function __invoke(
        TournamentSessionTeam $sessionTeam,
        SessionSquadService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->deleteSquad($sessionTeam, $user->getPlayer());
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
