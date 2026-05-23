<?php

declare(strict_types=1);

namespace App\Common\Controller\Moderator\PlayerClaim;

use App\Common\Attribute\RateLimited;
use App\Common\DTO\Request\PlayerClaimApproveRequestDTO;
use App\Common\Exception\PlayerClaimException;
use App\Common\Service\PlayerClaimService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderator/player-claims/{id}/approve', name: 'moderator_player_claim_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('moderator')]
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
        }

        return $this->json(['success' => true]);
    }
}
