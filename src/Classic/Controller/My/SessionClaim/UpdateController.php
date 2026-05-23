<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\SessionClaim;

use App\Common\Attribute\RateLimited;
use App\Classic\DTO\Request\Session\UpdateRequestDTO;
use App\Classic\Entity\TournamentSession;
use App\Common\Entity\User;
use App\Classic\Service\SessionClaimService;
use DateMalformedStringException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims/{id}/update', name: 'my_session_claim_update', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class UpdateController extends AbstractController
{
    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(
        TournamentSession $session,
        #[MapRequestPayload] UpdateRequestDTO $dto,
        SessionClaimService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->update($session, $user->getPlayer(), $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
