<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\SessionClaim\Squad;

use App\Classic\DTO\Response\My\SquadPlayerEditDTO;
use App\Classic\DTO\Response\My\SquadSessionDTO;
use App\Classic\DTO\Response\My\SquadSessionTeamDTO;
use App\Classic\Entity\TournamentSessionTeam;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\TournamentSessionTeamPlayerRepository;
use App\Classic\Service\SessionSquadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-teams/{id}/edit', name: 'my_session_team_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
class SquadEditController extends AbstractController
{
    public function __invoke(
        TournamentSessionTeam $sessionTeam,
        SessionSquadService $service,
        TournamentSessionTeamPlayerRepository $playerRepository,
        Mapper $mapper,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $session = $sessionTeam->getTournamentSession();
        $service->ensureCanManageSquad($session, $user->getPlayer());

        $players = $playerRepository->findBySessionTeamIds([$sessionTeam->getId()]);

        return $this->render('my/session_claim_squad.html.twig', [
            'session' => $mapper->map($session, SquadSessionDTO::class),
            'editSessionTeam' => $mapper->map($sessionTeam, SquadSessionTeamDTO::class),
            'editPlayers' => $mapper->mapMultiple($players, SquadPlayerEditDTO::class),
        ]);
    }
}
