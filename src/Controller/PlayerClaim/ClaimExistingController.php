<?php

declare(strict_types=1);

namespace App\Controller\PlayerClaim;

use App\DTO\Request\ClaimExistingRequestDTO;
use App\Entity\User;
use App\Exception\PlayerClaimException;
use App\Service\PlayerClaimService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/player-claim/existing', name: 'player_claim_existing', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class ClaimExistingController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] ClaimExistingRequestDTO $dto,
        PlayerClaimService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->claimExisting($dto, $user);
        } catch (PlayerClaimException $ex) {
            $code = $ex->getMessage() === 'common.not_found' ? 404 : 422;
            return $this->json(['error' => $ex->getMessage()], $code);
        } catch (Throwable) {
            return $this->json(['error' => 'common.error'], 500);
        }

        return $this->json(['success' => true], 201);
    }
}
