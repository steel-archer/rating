<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim\Dispute;

use App\Attribute\RateLimited;
use App\Entity\TournamentSessionTeamAnswer;
use App\Entity\User;
use App\Service\DisputeService;
use App\Service\SessionSquadService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-disputes/{id}/delete', name: 'my_session_claim_dispute_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class DeleteController extends AbstractController
{
    public function __invoke(
        TournamentSessionTeamAnswer $answer,
        SessionSquadService $squadService,
        DisputeService $disputeService,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $session = $answer->getTournamentSessionTeam()->getTournamentSession();
        $squadService->ensureCanManageSquad($session, $user->getPlayer());

        try {
            $disputeService->deleteDispute($answer);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
