<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim\Dispute;

use App\DTO\Response\My\DisputeDTO;
use App\DTO\Response\My\ResultsSessionDTO;
use App\DTO\Response\My\SquadSessionTeamDTO;
use App\Entity\TournamentSession;
use App\Entity\User;
use App\Mapping\Mapper;
use App\Repository\TournamentSessionTeamAnswerRepository;
use App\Repository\TournamentSessionTeamRepository;
use App\Service\SessionSquadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims/{id}/disputes', name: 'my_session_claim_disputes', requirements: ['id' => '\d+'], methods: ['GET'])]
class ListController extends AbstractController
{
    public function __invoke(
        TournamentSession $session,
        SessionSquadService $squadService,
        TournamentSessionTeamAnswerRepository $answerRepository,
        TournamentSessionTeamRepository $sessionTeamRepository,
        Mapper $mapper,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $squadService->ensureCanManageSquad($session, $user->getPlayer());

        $disputes = $answerRepository->findDisputesBySession($session);
        $sessionTeams = $sessionTeamRepository->findBySessionWithTeamAndTown($session);

        return $this->render('my/session_claim_disputes.html.twig', [
            'session' => $mapper->map($session, ResultsSessionDTO::class),
            'disputes' => $mapper->mapMultiple($disputes, DisputeDTO::class),
            'teams' => $mapper->mapMultiple($sessionTeams, SquadSessionTeamDTO::class),
        ]);
    }
}
