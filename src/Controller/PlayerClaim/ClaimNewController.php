<?php

declare(strict_types=1);

namespace App\Controller\PlayerClaim;

use App\Attribute\RateLimited;
use App\DTO\Request\ClaimNewRequestDTO;
use App\Entity\User;
use App\Exception\PlayerClaimException;
use App\Service\PlayerClaimService;
use App\Service\UserContactsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/player-claim/new', name: 'player_claim_new', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
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
