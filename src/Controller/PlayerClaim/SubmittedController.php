<?php

namespace App\Controller\PlayerClaim;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/player-claim/submitted', name: 'player_claim_submitted', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class SubmittedController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('player_claim/submitted.html.twig');
    }
}
