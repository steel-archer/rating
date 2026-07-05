<?php

declare(strict_types=1);

namespace App\Classic\Controller\Moderator\CaptainClaim;

use App\Classic\DTO\Request\CaptainClaim\RejectCaptainClaimDTO;
use App\Classic\Entity\CaptainClaim;
use App\Classic\Service\CaptainClaimService;
use App\Common\Attribute\RateLimited;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderator/captain-claims/{id}/reject', name: 'moderator_captain_claim_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('moderator')]
class RejectController extends AbstractController
{
    public function __invoke(
        CaptainClaim $claim,
        #[MapRequestPayload] RejectCaptainClaimDTO $dto,
        CaptainClaimService $service,
    ): JsonResponse {
        try {
            $service->reject($claim, $dto->comment);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
