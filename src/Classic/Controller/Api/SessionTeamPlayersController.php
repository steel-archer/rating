<?php

declare(strict_types=1);

namespace App\Classic\Controller\Api;

use App\Common\Attribute\RateLimited;
use App\Classic\Entity\Team;
use App\Classic\Entity\TournamentSession;
use App\Classic\Security\SessionRepresentativeVoter;
use App\Classic\Service\SessionSquadService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/api/session/{sessionId}/team/{teamId}/players',
    name: 'api_session_team_players',
    requirements: ['sessionId' => '\d+', 'teamId' => '\d+'],
    methods: ['GET'],
)]
#[RateLimited('api_suggest')]
class SessionTeamPlayersController extends AbstractController
{
    public function __invoke(
        #[MapEntity(id: 'sessionId')] TournamentSession $session,
        #[MapEntity(id: 'teamId')] Team $team,
        SessionSquadService $service,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(SessionRepresentativeVoter::MANAGE, $session);

        return $this->json($service->getTeamPlayerSuggestions($session, $team));
    }
}
