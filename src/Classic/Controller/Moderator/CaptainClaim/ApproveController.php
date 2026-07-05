<?php

declare(strict_types=1);

namespace App\Classic\Controller\Moderator\CaptainClaim;

use App\Classic\Entity\CaptainClaim;
use App\Classic\Service\CaptainClaimService;
use App\Common\Attribute\RateLimited;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderator/captain-claims/{id}/approve', name: 'moderator_captain_claim_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('moderator')]
class ApproveController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(
        CaptainClaim $claim,
        CaptainClaimService $service,
    ): JsonResponse {
        try {
            $service->approve($claim);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
