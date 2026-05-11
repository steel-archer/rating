<?php

declare(strict_types=1);

namespace App\Controller\PlayerClaim;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/player-claim/submitted', name: 'player_claim_submitted', methods: ['GET'])]
class SubmittedController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('player_claim/submitted.html.twig');
    }
}
