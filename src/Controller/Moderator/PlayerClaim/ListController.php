<?php

declare(strict_types=1);

namespace App\Controller\Moderator\PlayerClaim;

use App\DTO\Response\Moderator\PlayerClaimDTO;
use App\Mapping\Mapper;
use App\Repository\PlayerClaimRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/moderator/player-claims', name: 'moderator_player_claims', methods: ['GET'])]
#[IsGranted('ROLE_MODERATOR')]
class ListController extends AbstractController
{
    public function __invoke(PlayerClaimRepository $claimRepository, Mapper $mapper): Response
    {
        return $this->render('moderator/player_claims.html.twig', [
            'claims' => $mapper->mapMultiple($claimRepository->findPending(), PlayerClaimDTO::class),
        ]);
    }
}
