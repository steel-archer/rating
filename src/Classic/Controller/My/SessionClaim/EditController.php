<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\SessionClaim;

use App\Classic\DTO\Response\My\SessionClaimEditDTO;
use App\Classic\DTO\Response\My\TournamentDocumentDTO;
use App\Classic\Entity\TournamentSession;
use App\Classic\Enum\SessionClaimStatus;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\SessionClaimRepository;
use App\Classic\Repository\TournamentDocumentRepository;
use App\Classic\Security\SessionRepresentativeVoter;
use App\Classic\Service\SessionResultService;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims/{id}/edit', name: 'my_session_claim_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
class EditController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function __invoke(
        TournamentSession $session,
        SessionClaimRepository $claimRepository,
        TournamentDocumentRepository $documentRepository,
        SessionResultService $resultService,
        Mapper $mapper,
    ): Response {
        $this->denyAccessUnlessGranted(SessionRepresentativeVoter::MANAGE, $session);

        $claim = $claimRepository->findBySession($session);
        if ($claim === null) {
            throw $this->createNotFoundException();
        }

        $documents = [];
        if ($claim->getStatus() === SessionClaimStatus::Approved) {
            $documents = $mapper->mapMultiple(
                $documentRepository->findByTournament($session->getTournament()),
                TournamentDocumentDTO::class,
            );
        }

        $canEnterResults = $claim->getStatus() === SessionClaimStatus::Approved
            && $session->getPlayedAt() !== null
            && $session->getPlayedAt() <= new DateTimeImmutable('today')
            && $session->getTournament()->isSubmissionOpen();

        $isRegistrationOpen = $session->getTournament()->isRegistrationOpen();

        $teams = [];
        if ($canEnterResults) {
            $teams = $resultService->getAllSessionTeams($session);
        }

        return $this->render('my/session_claim_edit.html.twig', [
            'claim' => $mapper->map($claim, SessionClaimEditDTO::class),
            'documents' => $documents,
            'canEnterResults' => $canEnterResults,
            'isRegistrationOpen' => $isRegistrationOpen,
            'teams' => $teams,
        ]);
    }
}
