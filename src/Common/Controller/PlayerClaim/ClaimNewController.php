<?php

declare(strict_types=1);

namespace App\Common\Controller\PlayerClaim;

use App\Common\Attribute\RateLimited;
use App\Common\DTO\Request\ClaimNewRequestDTO;
use App\Common\Entity\User;
use App\Common\Exception\PlayerClaimException;
use App\Common\Service\PlayerClaimService;
use App\Common\Service\UserContactsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/player-claim/new', name: 'player_claim_new', methods: ['POST'])]
#[RateLimited('claim')]
class ClaimNewController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] ClaimNewRequestDTO $dto,
        PlayerClaimService $service,
        UserContactsService $contactsService,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $contactsService->updateFromDto($user, $dto);

        try {
            $service->claimNew($dto, $user);
        } catch (PlayerClaimException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true], 201);
    }
}
