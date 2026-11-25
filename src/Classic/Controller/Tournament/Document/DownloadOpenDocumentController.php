<?php

declare(strict_types=1);

namespace App\Classic\Controller\Tournament\Document;

use App\Classic\Entity\TournamentDocument;
use App\Classic\Service\TournamentAccessService;
use App\Classic\Service\TournamentDocumentService;
use App\Common\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves open tournament documents (e.g. regulations) to any player.
 * Downloads are intentionally not logged.
 */
#[Route('/tournament/documents/{id}/download', name: 'tournament_document_download', requirements: ['id' => '\d+'], methods: ['GET'])]
class DownloadOpenDocumentController extends AbstractController
{
    /**
     * @throws NotFoundHttpException
     */
    public function __invoke(
        TournamentDocument $document,
        TournamentAccessService $accessService,
        TournamentDocumentService $service,
    ): BinaryFileResponse {
        /** @var User $user */
        $user = $this->getUser();

        // Closed documents are never served here; drafts follow the tournament visibility.
        if (
            !$document->isOpen()
            || !$accessService->isVisible(
                $document->getTournament(),
                $user->getPlayer(),
                $this->isGranted('ROLE_MODERATOR'),
            )
        ) {
            throw $this->createNotFoundException();
        }

        $response = $this->file($service->getFilePath($document), $document->getOriginalName());
        $response->headers->set('Cache-Control', 'private');

        return $response;
    }
}
