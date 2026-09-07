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

        $canEnterResults = $claim->getStatus() === SessionClaimStatus::Approved
            && $session->getPlayedAt() !== null
            && $session->getPlayedAt() <= new DateTimeImmutable('today')
            && $session->getTournament()->isSubmissionOpen();

        $isCentralized = $session->getTournament()->getFormat() === TournamentFormat::Centralized;
        $isRegistrationOpen = $session->getTournament()->isRegistrationOpen();
        $tournamentStartedAt = $session->getTournament()->getStartedAt();
        $tournamentEndedAt = $session->getTournament()->getEndedAt();

        $teams = [];
        if ($canEnterResults) {
            $teams = $resultService->getAllSessionTeams($session);
        }

        return $this->render('my/session_claim_edit.html.twig', [
            'claim' => $mapper->map($claim, SessionClaimEditDTO::class),
            'canEnterResults' => $canEnterResults,
            'isCentralized' => $isCentralized,
            'isRegistrationOpen' => $isRegistrationOpen,
            'tournamentStartedAt' => $tournamentStartedAt,
            'tournamentEndedAt' => $tournamentEndedAt,
            'teams' => $teams,
        ]);
    }
}
