<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim;

use App\DTO\Response\My\SessionClaimEditDTO;
use App\DTO\Response\My\TournamentDocumentDTO;
use App\Entity\TournamentSession;
use App\Enum\SessionClaimStatus;
use App\Mapping\Mapper;
use App\Repository\SessionClaimRepository;
use App\Repository\TournamentDocumentRepository;
use App\Security\SessionRepresentativeVoter;
use App\Service\SessionResultService;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims/{id}/edit', name: 'my_session_claim_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
class EditController extends AbstractController
{
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
            && $session->getPlayedAt() <= new DateTimeImmutable('today');

        $teams = [];
        if ($canEnterResults) {
            $teams = $resultService->getSessionResults($session);
        }

        return $this->render('my/session_claim_edit.html.twig', [
            'claim' => $mapper->map($claim, SessionClaimEditDTO::class),
            'documents' => $documents,
            'canEnterResults' => $canEnterResults,
            'teams' => $teams,
        ]);
    }
}
