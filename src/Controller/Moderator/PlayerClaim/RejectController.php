<?php

declare(strict_types=1);

namespace App\Controller\Moderator\PlayerClaim;

use App\Attribute\RateLimited;
use App\Exception\PlayerClaimException;
use App\Service\PlayerClaimService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderator/player-claims/{id}/reject', name: 'moderator_player_claim_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('moderator')]
class RejectController extends AbstractController
{
    public function __invoke(int $id, PlayerClaimService $claimService): JsonResponse
    {
        try {
            $claimService->reject($id);
        } catch (PlayerClaimException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
