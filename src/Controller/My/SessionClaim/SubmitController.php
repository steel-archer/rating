<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim;

use App\Attribute\RateLimited;
use App\DTO\Request\Session\ClaimRequestDTO;
use App\Entity\Tournament;
use App\Entity\User;
use App\Service\SessionClaimService;
use DateMalformedStringException;
use LogicException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims/{tournamentId}/submit', name: 'my_session_claim_submit', requirements: ['tournamentId' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class SubmitController extends AbstractController
{
    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(
        #[MapEntity(id: 'tournamentId')] Tournament $tournament,
        #[MapRequestPayload] ClaimRequestDTO $dto,
        SessionClaimService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->submit($tournament, $user->getPlayer(), $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
