<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Team;
use App\Entity\TournamentSession;
use App\Repository\TeamPlayerRepository;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Security\SessionRepresentativeVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(
    '/api/session/{sessionId}/team/{teamId}/players',
    name: 'api_session_team_players',
    requirements: ['sessionId' => '\d+', 'teamId' => '\d+'],
    methods: ['GET'],
)]
#[IsGranted('ROLE_PLAYER')]
class SessionTeamPlayersController extends AbstractController
{
    public function __invoke(
        #[MapEntity(id: 'sessionId')] TournamentSession $session,
        #[MapEntity(id: 'teamId')] Team $team,
        TeamPlayerRepository $teamPlayerRepository,
        TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(SessionRepresentativeVoter::MANAGE, $session);

        $season = $session->getTournament()->getSeason();
        $baseSquadPlayerIds = [];
        $result = [];

        if ($season !== null) {
            $squadMap = $teamPlayerRepository->getSquadMapBySeason($season);
            $squadInfo = $squadMap[$team->getId()] ?? null;

            if ($squadInfo !== null) {
                $baseSquadPlayerIds = $squadInfo['playerIds'];
                $captainId = $squadInfo['captainId'];
                $baseSquadPlayers = $teamPlayerRepository->findPlayersForTeamAndSeason($team, $season);

                foreach ($baseSquadPlayers as $player) {
                    $result[] = [
                        'id' => $player->getId(),
                        'name' => $player->getFullName(),
                        'group' => 'base',
                        'isCaptain' => $player->getId() === $captainId,
                    ];
                }
            }

            $seasonPlayers = $sessionTeamPlayerRepository->findPlayersByTeamAndSeason($team, $season);
            foreach ($seasonPlayers as $player) {
                if (!in_array($player->getId(), $baseSquadPlayerIds, true)) {
                    $result[] = [
                        'id' => $player->getId(),
                        'name' => $player->getFullName(),
                        'group' => 'season',
                    ];
                }
            }
        }

        return $this->json($result);
    }
}
