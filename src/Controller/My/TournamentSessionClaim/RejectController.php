<?php

declare(strict_types=1);

namespace App\Controller\My\TournamentSessionClaim;

use App\DTO\Request\Session\RejectRequestDTO;
use App\Entity\TournamentSession;
use App\Entity\User;
use App\Service\SessionClaimService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/tournament-claims/{id}/reject', name: 'my_tournament_claim_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
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
            $service->reject($session, $user, $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
