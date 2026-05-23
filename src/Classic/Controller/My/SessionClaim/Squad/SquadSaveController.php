<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\SessionClaim\Squad;

use App\Common\Attribute\RateLimited;
use App\Classic\DTO\Request\Session\SquadRequestDTO;
use App\Classic\Entity\TournamentSession;
use App\Common\Entity\User;
use App\Classic\Service\SessionSquadService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims/{id}/squad', name: 'my_session_claim_squad_save', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class SquadSaveController extends AbstractController
{
    public function __invoke(
        TournamentSession $session,
        #[MapRequestPayload] SquadRequestDTO $dto,
        SessionSquadService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->saveSquad($session, $user->getPlayer(), $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        $this->addFlash('success', 'squad.saved');

        return $this->json(['success' => true]);
    }
}
