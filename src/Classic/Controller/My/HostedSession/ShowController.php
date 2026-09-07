<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\HostedSession;

use App\Classic\DTO\Response\My\HostedSessionDetailDTO;
use App\Classic\DTO\Response\My\TournamentDocumentDTO;
use App\Classic\Entity\TournamentSession;
use App\Classic\Repository\SessionClaimRepository;
use App\Classic\Repository\TournamentDocumentRepository;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/hosted-sessions/{id}', name: 'my_hosted_session_show', requirements: ['id' => '\d+'], methods: ['GET'])]
class ShowController extends AbstractController
{
    public function __invoke(
        TournamentSession $session,
        SessionClaimRepository $claimRepository,
        TournamentDocumentRepository $documentRepository,
        Mapper $mapper,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();
        $tournament = $session->getTournament();

        // The question package is tournament-scoped: any host of an approved session in
        // this tournament may access it. This mirrors DownloadDocumentController so both
        // entry points share the same access rule. The session must still be one this
        // player hosts, so the {id} in the URL is meaningful.
        if (
            $player === null
            || $session->getHost()->getId() !== $player->getId()
            || !$claimRepository->hasApprovedHostedSession($player, $tournament)
        ) {
            throw $this->createNotFoundException();
        }

        $documents = $mapper->mapMultiple(
            $documentRepository->findByTournament($tournament),
            TournamentDocumentDTO::class,
        );

        return $this->render('my/hosted_session_show.html.twig', [
            'session' => $mapper->map(
                $session,
                HostedSessionDetailDTO::class,
                ['documents' => $documents],
            ),
        ]);
    }
}
