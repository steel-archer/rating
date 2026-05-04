<?php

namespace App\Controller\Moderator;

use App\Repository\PlayerClaimRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/moderator/claims', name: 'moderator_claims', methods: ['GET'])]
#[IsGranted('ROLE_MODERATOR')]
final class ClaimListController extends AbstractController
{
    public function __invoke(PlayerClaimRepository $claimRepository): Response
    {
        return $this->render('moderator/claims.html.twig', [
            'claims' => $claimRepository->findPending(),
        ]);
    }
}
