<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\SessionClaim;

use App\Classic\DTO\Response\My\SessionClaimListDTO;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\SessionClaimRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims', name: 'my_session_claims', methods: ['GET'])]
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
