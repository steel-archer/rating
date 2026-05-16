<?php

declare(strict_types=1);

namespace App\Controller\My\TournamentSessionClaim;

use App\Entity\User;
use App\Service\SessionClaimService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/tournament-claims', name: 'my_tournament_claims', methods: ['GET'])]
class ListController extends AbstractController
{
    public function __invoke(SessionClaimService $service): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        return $this->render('my/tournament_session_claims.html.twig', [
            'grouped' => $service->getPendingClaimsByOrganizer($player),
            'active' => $service->getActiveClaimsByOrganizer($player),
        ]);
    }
}
