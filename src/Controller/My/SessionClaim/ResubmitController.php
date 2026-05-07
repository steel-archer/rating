<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim;

use App\Entity\User;
use App\Repository\TournamentSessionRepository;
use App\Service\SessionClaimService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/my/session-claims/{id}/resubmit', name: 'my_session_claim_resubmit', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
class ResubmitController extends AbstractController
{
    public function __invoke(
        int $id,
        TournamentSessionRepository $sessionRepository,
        SessionClaimService $service,
    ): JsonResponse {
        $session = $sessionRepository->find($id);
        if ($session === null) {
            return $this->json(['error' => 'common.not_found'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->resubmit($session, $user);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        } catch (Throwable) {
            return $this->json(['error' => 'common.error'], 500);
        }

        return $this->json(['success' => true]);
    }
}
