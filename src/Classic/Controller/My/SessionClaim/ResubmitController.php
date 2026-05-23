<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\SessionClaim;

use App\Common\Attribute\RateLimited;
use App\Classic\Entity\TournamentSession;
use App\Common\Entity\User;
use App\Classic\Service\SessionClaimService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims/{id}/resubmit', name: 'my_session_claim_resubmit', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class ResubmitController extends AbstractController
{
    public function __invoke(
        TournamentSession $session,
        SessionClaimService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->resubmit($session, $user->getPlayer());
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
