<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\SessionClaim\Dispute;

use App\Classic\DTO\Response\My\DisputeDTO;
use App\Classic\DTO\Response\My\ResultsSessionDTO;
use App\Classic\DTO\Response\My\SquadSessionTeamDTO;
use App\Classic\Entity\TournamentSession;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\TournamentSessionTeamAnswerRepository;
use App\Classic\Repository\TournamentSessionTeamRepository;
use App\Classic\Service\SessionSquadService;
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
