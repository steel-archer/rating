<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim;

use App\DTO\Response\My\SessionClaimListDTO;
use App\Entity\User;
use App\Mapping\Mapper;
use App\Repository\SessionClaimRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/session-claims', name: 'my_session_claims', methods: ['GET'])]
#[IsGranted('ROLE_PLAYER')]
class ListController extends AbstractController
{
    public function __invoke(SessionClaimRepository $claimRepository, Mapper $mapper): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $claims = $mapper->mapMultiple(
            $claimRepository->findByPlayer($user->getPlayer()),
            SessionClaimListDTO::class,
        );

        return $this->render('my/session_claims.html.twig', [
            'claims' => $claims,
        ]);
    }
}
