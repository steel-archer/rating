<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim;

use App\DTO\Request\Session\UpdateRequestDTO;
use App\Entity\TournamentSession;
use App\Entity\User;
use App\Service\SessionClaimService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/session-claims/{id}/update', name: 'my_session_claim_update', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
class UpdateController extends AbstractController
{
    public function __invoke(
        TournamentSession $session,
        #[MapRequestPayload] UpdateRequestDTO $dto,
        SessionClaimService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->update($session, $user, $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
