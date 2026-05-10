<?php

declare(strict_types=1);

namespace App\Controller\My\TournamentSessionClaim;

use App\Attribute\RateLimited;
use App\Entity\TournamentSession;
use App\Entity\User;
use App\Service\SessionClaimService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/tournament-claims/{id}/approve', name: 'my_tournament_claim_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
#[RateLimited('mutation')]
class ApproveController extends AbstractController
{
    public function __invoke(
        TournamentSession $session,
        SessionClaimService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->approve($session, $user->getPlayer());
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
