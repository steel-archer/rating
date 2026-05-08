<?php

declare(strict_types=1);

namespace App\Controller\Moderator\PlayerClaim;

use App\DTO\Request\PlayerClaimApproveRequestDTO;
use App\Exception\PlayerClaimException;
use App\Service\PlayerClaimService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/moderator/player-claims/{id}/approve', name: 'moderator_player_claim_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_MODERATOR')]
class ApproveController extends AbstractController
{
    public function __invoke(
        int $id,
        #[MapRequestPayload] PlayerClaimApproveRequestDTO $dto,
        PlayerClaimService $claimService,
    ): JsonResponse {
        try {
            $claimService->approve($id, $dto->townName);
        } catch (PlayerClaimException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        } catch (Throwable) {
            return $this->json(['error' => 'common.error'], 500);
        }

        return $this->json(['success' => true]);
    }
}
