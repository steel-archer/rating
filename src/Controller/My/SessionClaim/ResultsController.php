<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim;

use App\DTO\Response\My\ResultsSessionDTO;
use App\Entity\TournamentSession;
use App\Entity\User;
use App\Mapping\Mapper;
use App\Service\SessionResultService;
use App\Service\SessionSquadService;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims/{id}/results', name: 'my_session_claim_results', requirements: ['id' => '\d+'], methods: ['GET'])]
class ResultsController extends AbstractController
{
    /**
     * @throws DbalException
     */
    public function __invoke(
        TournamentSession $session,
        SessionSquadService $squadService,
        SessionResultService $resultService,
        Mapper $mapper,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $squadService->ensureCanManageSquad($session, $user->getPlayer());

        $breakdown = $resultService->getAnswerBreakdown($session);

        return $this->render('my/session_claim_results.html.twig', [
            'session' => $mapper->map($session, ResultsSessionDTO::class),
            'teams' => $resultService->getSessionResults($session),
            'hasUnsubmittedResults' => $resultService->hasUnsubmittedResults($session),
            'breakdown' => $breakdown,
        ]);
    }
}
