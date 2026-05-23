<?php

declare(strict_types=1);

namespace App\Common\Controller\PlayerClaim;

use App\Common\Attribute\RateLimited;
use App\Common\DTO\Request\ClaimExistingRequestDTO;
use App\Common\Entity\User;
use App\Common\Exception\PlayerClaimException;
use App\Common\Service\PlayerClaimService;
use App\Common\Service\UserContactsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/player-claim/existing', name: 'player_claim_existing', methods: ['POST'])]
#[RateLimited('claim')]
class ClaimExistingController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] ClaimExistingRequestDTO $dto,
        PlayerClaimService $service,
        UserContactsService $contactsService,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $contactsService->updateFromDto($user, $dto);

        try {
            $service->claimExisting($dto, $user);
        } catch (PlayerClaimException $ex) {
            $code = $ex->getMessage() === 'common.not_found' ? 404 : 422;
            return $this->json(['error' => $ex->getMessage()], $code);
        }

        return $this->json(['success' => true], 201);
    }
}
