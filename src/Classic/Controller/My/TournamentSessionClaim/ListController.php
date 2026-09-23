<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\TournamentSessionClaim;

use App\Common\Entity\User;
use App\Classic\Service\SessionClaimService;
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
        $claims = $service->getClaimGroupsByOrganizer($user->getPlayer());

        return $this->render('my/tournament_session_claims.html.twig', [
            'grouped' => $claims->pending,
            'active' => $claims->approved,
            'rejected' => $claims->rejected,
        ]);
    }
}
