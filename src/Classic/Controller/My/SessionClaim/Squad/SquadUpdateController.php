<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\SessionClaim\Squad;

use App\Common\Attribute\RateLimited;
use App\Classic\DTO\Request\Session\SquadRequestDTO;
use App\Classic\Entity\TournamentSessionTeam;
use App\Common\Entity\User;
use App\Classic\Service\SessionSquadService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-teams/{id}/update', name: 'my_session_team_update', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class SquadUpdateController extends AbstractController
{
    public function __invoke(
        TournamentSessionTeam $sessionTeam,
        #[MapRequestPayload] SquadRequestDTO $dto,
        SessionSquadService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->updateSquad($sessionTeam, $user->getPlayer(), $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        $this->addFlash('success', 'squad.saved');

        return $this->json(['success' => true]);
    }
}
