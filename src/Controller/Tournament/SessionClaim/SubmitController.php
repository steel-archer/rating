<?php

declare(strict_types=1);

namespace App\Controller\Tournament\SessionClaim;

use App\DTO\Request\Session\ClaimRequestDTO;
use App\Entity\User;
use App\Repository\TournamentRepository;
use App\Service\SessionClaimService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/tournament/{id}/session-claims', name: 'tournament_session_claim_submit', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
class SubmitController extends AbstractController
{
    public function __invoke(
        int $id,
        #[MapRequestPayload] ClaimRequestDTO $dto,
        TournamentRepository $tournamentRepository,
        SessionClaimService $service,
    ): JsonResponse {
        $tournament = $tournamentRepository->find($id);
        if ($tournament === null) {
            return $this->json(['error' => 'common.not_found'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->submit($tournament, $user, $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        } catch (Throwable) {
            return $this->json(['error' => 'common.error'], 500);
        }

        return $this->json(['success' => true]);
    }
}
