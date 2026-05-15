<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim\Dispute;

use App\Attribute\RateLimited;
use App\DTO\Request\Session\DisputeCreateRequestDTO;
use App\Entity\TournamentSession;
use App\Entity\User;
use App\Service\DisputeService;
use App\Service\SessionSquadService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims/{id}/disputes/create', name: 'my_session_claim_dispute_create', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class CreateController extends AbstractController
{
    public function __invoke(
        TournamentSession $session,
        #[MapRequestPayload] DisputeCreateRequestDTO $dto,
        SessionSquadService $squadService,
        DisputeService $disputeService,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $squadService->ensureCanManageSquad($session, $user->getPlayer());

        try {
            $disputeService->createDispute(
                $session,
                $dto->sessionTeamId,
                $dto->questionNumber,
                trim($dto->text ?? ''),
            );
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
