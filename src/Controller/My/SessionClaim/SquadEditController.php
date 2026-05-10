<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim;

use App\DTO\Response\My\SquadPlayerEditDTO;
use App\DTO\Response\My\SquadSessionDTO;
use App\DTO\Response\My\SquadSessionTeamDTO;
use App\Entity\TournamentSessionTeam;
use App\Entity\User;
use App\Mapping\Mapper;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Service\SessionSquadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/session-teams/{id}/edit', name: 'my_session_team_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
#[IsGranted('ROLE_PLAYER')]
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
