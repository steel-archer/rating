<?php

declare(strict_types=1);

namespace App\Common\Controller\Moderator\PlayerClaim;

use App\Common\DTO\Response\Moderator\PlayerClaimDTO;
use App\Common\Mapping\Mapper;
use App\Common\Repository\PlayerClaimRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderator/player-claims', name: 'moderator_player_claims', methods: ['GET'])]
class ListController extends AbstractController
{
    public function __invoke(PlayerClaimRepository $claimRepository, Mapper $mapper): Response
    {
        return $this->render('moderator/player_claims.html.twig', [
            'claims' => $mapper->mapMultiple($claimRepository->findPending(), PlayerClaimDTO::class),
        ]);
    }
}
