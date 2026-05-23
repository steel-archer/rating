<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\SessionClaim\Dispute;

use App\Common\Attribute\RateLimited;
use App\Classic\DTO\Request\Session\DisputeSubmitRequestDTO;
use App\Classic\Entity\TournamentSession;
use App\Common\Entity\User;
use App\Classic\Service\DisputeService;
use App\Classic\Service\SessionSquadService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims/{id}/disputes/submit', name: 'my_session_claim_dispute_submit', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class SubmitController extends AbstractController
{
    public function __invoke(
        TournamentSession $session,
        #[MapRequestPayload] DisputeSubmitRequestDTO $dto,
        SessionSquadService $squadService,
        DisputeService $disputeService,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $squadService->ensureCanManageSquad($session, $user->getPlayer());

        try {
            $disputeService->submitDisputes($session, $dto->ids);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
