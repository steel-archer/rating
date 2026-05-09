<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim;

use App\DTO\Response\My\SessionClaimEditDTO;
use App\DTO\Response\My\TournamentDocumentDTO;
use App\Entity\TournamentSession;
use App\Entity\User;
use App\Enum\SessionClaimStatus;
use App\Mapping\Mapper;
use App\Repository\SessionClaimRepository;
use App\Repository\TournamentDocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/session-claims/{id}/edit', name: 'my_session_claim_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
#[IsGranted('ROLE_PLAYER')]
class EditController extends AbstractController
{
    public function __invoke(
        TournamentSession $session,
        SessionClaimRepository $claimRepository,
        TournamentDocumentRepository $documentRepository,
        Mapper $mapper,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($session->getRepresentative()->getId() !== $user->getPlayer()?->getId()) {
            throw $this->createAccessDeniedException();
        }

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

        return $this->render('my/session_claim_edit.html.twig', [
            'claim' => $mapper->map($claim, SessionClaimEditDTO::class),
            'documents' => $documents,
        ]);
    }
}
