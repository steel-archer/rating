<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\TournamentSessionClaim;

use App\Common\Attribute\RateLimited;
use App\Classic\DTO\Request\Session\RejectRequestDTO;
use App\Classic\Entity\TournamentSession;
use App\Common\Entity\User;
use App\Classic\Service\SessionClaimService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/tournament-claims/{id}/reject', name: 'my_tournament_claim_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class RejectController extends AbstractController
{
    public function __invoke(
        TournamentSession $session,
        #[MapRequestPayload] RejectRequestDTO $dto,
        SessionClaimService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->reject($session, $user->getPlayer(), $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
