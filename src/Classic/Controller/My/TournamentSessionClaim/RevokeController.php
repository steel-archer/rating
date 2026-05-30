<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\TournamentSessionClaim;

use App\Common\Attribute\RateLimited;
use App\Classic\Entity\TournamentSession;
use App\Common\Entity\User;
use App\Classic\Service\SessionClaimService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/tournament-claims/{id}/revoke', name: 'my_tournament_claim_revoke', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class RevokeController extends AbstractController
{
    public function __invoke(
        TournamentSession $session,
        SessionClaimService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->revoke($session, $user->getPlayer());
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
