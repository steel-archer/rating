<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\HostedSession;

use App\Classic\DTO\Response\My\HostedSessionListItemDTO;
use App\Classic\Entity\SessionClaim;
use App\Classic\Repository\SessionClaimRepository;
use App\Classic\Repository\TournamentDocumentRepository;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/hosted-sessions', name: 'my_hosted_sessions', methods: ['GET'])]
class ListController extends AbstractController
{
    public function __invoke(
        SessionClaimRepository $claimRepository,
        TournamentDocumentRepository $documentRepository,
        Mapper $mapper,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        if ($player === null) {
            throw $this->createNotFoundException();
        }

        $claims = $claimRepository->findApprovedHostedByPlayer($player);

        $tournamentIds = array_values(array_unique(array_map(
            static fn(SessionClaim $claim) => $claim->getSession()->getTournament()->getId(),
            $claims,
        )));

        $documentCounts = $documentRepository->countByTournamentIds($tournamentIds);

        $sessions = $mapper->mapMultiple(
            $claims,
            HostedSessionListItemDTO::class,
            ['documentCounts' => $documentCounts],
        );

        return $this->render('my/hosted_sessions.html.twig', [
            'sessions' => $sessions,
        ]);
    }
}
