<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim;

use App\Entity\User;
use App\Repository\SessionClaimRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/session-claims', name: 'my_session_claims', methods: ['GET'])]
#[IsGranted('ROLE_PLAYER')]
class ListController extends AbstractController
{
    public function __invoke(SessionClaimRepository $claimRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('my/session_claims.html.twig', [
            'claims' => $claimRepository->findByPlayer($user->getPlayer()),
        ]);
    }
}
