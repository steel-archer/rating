<?php

namespace App\Controller\PlayerClaim;

use App\Entity\User;
use App\Repository\PlayerClaimRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/player-claim', name: 'player_claim_index', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class IndexController extends AbstractController
{
    public function __invoke(PlayerClaimRepository $claimRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPlayer() !== null || $user->isAdmin()) {
            return $this->redirectToRoute('home');
        }

        if ($claimRepository->hasPendingClaim($user)) {
            return $this->redirectToRoute('player_claim_submitted');
        }

        return $this->render('player_claim/index.html.twig');
    }
}
