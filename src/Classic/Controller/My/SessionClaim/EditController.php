<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\SessionClaim;

use App\Classic\DTO\Response\My\SessionClaimEditDTO;
use App\Classic\Entity\TournamentSession;
use App\Classic\Enum\SessionClaimStatus;
use App\Classic\Enum\TournamentFormat;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\SessionClaimRepository;
use App\Classic\Security\SessionRepresentativeVoter;
use App\Classic\Service\SessionResultService;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/my/session-claims/{id}/edit', name: 'my_session_claim_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
class EditController extends AbstractController
{
    /**
     * @throws AccessDeniedException
     * @throws Exception
     * @throws NotFoundHttpException
     */
    public function __invoke(
        TournamentSession $session,
        SessionClaimRepository $claimRepository,
        SessionResultService $resultService,
        Mapper $mapper,
    ): Response {
        $this->denyAccessUnlessGranted(SessionRepresentativeVoter::MANAGE, $session);

        $claim = $claimRepository->findBySession($session);
        if ($claim === null) {
            throw $this->createNotFoundException();
        }

        $today = new DateTimeImmutable('today');
        $playedAt = $session->getPlayedAt();
        $tournament = $session->getTournament();
        $isApproved = $claim->getStatus() === SessionClaimStatus::Approved;
        $isSubmissionOpen = $tournament->isSubmissionOpen();

        $canEnterResults = $isApproved
            && $playedAt !== null
            && $playedAt <= $today
            && $isSubmissionOpen;

        // Approved claim whose play day has not arrived yet: results are not editable,
        // but the representative should be told why and when they will be able to submit.
        $resultsPendingDate = $isApproved
            && $playedAt !== null
            && $playedAt > $today
            && $isSubmissionOpen;

        // Approved claim whose play day has arrived but the submission deadline has passed:
        // results can no longer be entered, so the representative should be told the window is closed.
        $resultsClosed = $isApproved
            && $playedAt !== null
            && $playedAt <= $today
            && !$isSubmissionOpen;

        $isCentralized = $tournament->getFormat() === TournamentFormat::Centralized;
        $isRegistrationOpen = $tournament->isRegistrationOpen();
        $tournamentStartedAt = $tournament->getStartedAt();
        $tournamentEndedAt = $tournament->getEndedAt();
        $submissionDeadline = $tournament->getSubmissionDeadline();

        $teams = [];
        if ($canEnterResults) {
            $teams = $resultService->getAllSessionTeams($session);
        }

        return $this->render('my/session_claim_edit.html.twig', [
            'claim' => $mapper->map($claim, SessionClaimEditDTO::class),
            'canEnterResults' => $canEnterResults,
            'resultsPendingDate' => $resultsPendingDate,
            'resultsClosed' => $resultsClosed,
            'playedAt' => $playedAt,
            'submissionDeadline' => $submissionDeadline,
            'isCentralized' => $isCentralized,
            'isRegistrationOpen' => $isRegistrationOpen,
            'tournamentStartedAt' => $tournamentStartedAt,
            'tournamentEndedAt' => $tournamentEndedAt,
            'teams' => $teams,
        ]);
    }
}
