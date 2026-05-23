<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\Tournament\Document;

use App\Classic\Entity\TournamentDocument;
use App\Common\Entity\User;
use App\Classic\Repository\SessionClaimRepository;
use App\Classic\Security\TournamentOrganizerVoter;
use App\Classic\Service\TournamentDocumentService;
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
