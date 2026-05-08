<?php

declare(strict_types=1);

namespace App\Controller\My\TournamentSessionClaim;

use App\DTO\Response\Tournament\SessionClaimDTO;
use App\Entity\User;
use App\Mapping\Mapper;
use App\Repository\SessionClaimRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/tournament-claims', name: 'my_tournament_claims', methods: ['GET'])]
#[IsGranted('ROLE_PLAYER')]
class ListController extends AbstractController
{
    public function __invoke(SessionClaimRepository $claimRepository, Mapper $mapper): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $claimsByTournament = $claimRepository->findPendingByOrganizer($user->getPlayer());

        $grouped = [];
        foreach ($claimsByTournament as $group) {
            $grouped[] = [
                'tournamentId' => $group['tournamentId'],
                'tournamentName' => $group['tournamentName'],
                'claims' => $mapper->mapMultiple($group['claims'], SessionClaimDTO::class),
            ];
        }

        return $this->render('my/tournament_session_claims.html.twig', [
            'grouped' => $grouped,
        ]);
    }
}
