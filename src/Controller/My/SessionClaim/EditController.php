<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim;

use App\Entity\User;
use App\Repository\SessionClaimRepository;
use App\Repository\TournamentSessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/session-claims/{id}/edit', name: 'my_session_claim_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
#[IsGranted('ROLE_PLAYER')]
class EditController extends AbstractController
{
    public function __invoke(
        int $id,
        TournamentSessionRepository $sessionRepository,
        SessionClaimRepository $claimRepository,
    ): Response {
        $session = $sessionRepository->find($id);
        if ($session === null) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($session->getRepresentative()->getId() !== $user->getPlayer()?->getId()) {
            throw $this->createAccessDeniedException();
        }

        $claim = $claimRepository->findBySession($session);
        if ($claim === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('my/session_claim_edit.html.twig', [
            'session' => $session,
            'claim' => $claim,
        ]);
    }
}
