<?php

declare(strict_types=1);

namespace App\Controller\My\Tournament\Document;

use App\Entity\TournamentDocument;
use App\Entity\User;
use App\Repository\SessionClaimRepository;
use App\Security\TournamentOrganizerVoter;
use App\Service\TournamentDocumentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/tournaments/documents/{id}/download', name: 'my_tournament_document_download', requirements: ['id' => '\d+'], methods: ['GET'])]
class DownloadDocumentController extends AbstractController
{
    public function __invoke(
        TournamentDocument $document,
        SessionClaimRepository $claimRepository,
        TournamentDocumentService $service,
    ): BinaryFileResponse {
        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();
        $tournament = $document->getTournament();

        $isOrganizer = $this->isGranted(TournamentOrganizerVoter::EDIT, $tournament);
        $hasApprovedSession = $claimRepository->hasApprovedByPlayerAndTournament(
            $player,
            $tournament,
        );

        if (!$isOrganizer && !$hasApprovedSession) {
            throw $this->createNotFoundException();
        }

        $path = $service->getFilePath($document);

        return $this->file($path, $document->getOriginalName());
    }
}
