<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim\Results;

use App\Attribute\RateLimited;
use App\Entity\TournamentSession;
use App\Entity\User;
use App\Service\SessionResultUploadService;
use App\Service\SessionSquadService;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims/{id}/results/submit', name: 'my_session_claim_results_submit', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class ResultsSubmitController extends AbstractController
{
    public function __invoke(
        TournamentSession $session,
        SessionSquadService $squadService,
        SessionResultUploadService $uploadService,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $squadService->ensureCanManageSquad($session, $user->getPlayer());

        try {
            $uploadService->submitResults($session);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        } catch (InvalidArgumentException) {
            return $this->json(['error' => 'common.error'], 500);
        }

        $this->addFlash('success', 'results.submitted');

        return $this->json([
            'success' => true,
            'redirect' => $this->generateUrl('my_session_claim_edit', ['id' => $session->getId()]),
        ]);
    }
}
