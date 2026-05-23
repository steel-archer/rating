<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\Tournament\Document;

use App\Common\Attribute\RateLimited;
use App\Classic\Entity\TournamentDocument;
use App\Classic\Security\TournamentOrganizerVoter;
use App\Classic\Service\TournamentDocumentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/tournaments/documents/{id}', name: 'my_tournament_document_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
#[RateLimited('mutation')]
class DeleteDocumentController extends AbstractController
{
    public function __invoke(
        TournamentDocument $document,
        TournamentDocumentService $service,
    ): JsonResponse {
        if (!$this->isGranted(TournamentOrganizerVoter::EDIT, $document->getTournament())) {
            return $this->json(['error' => 'common.not_found'], 404);
        }

        if ($document->getTournament()->isStarted()) {
            return $this->json(['error' => 'tournament.document.error.started'], 422);
        }

        $service->delete($document);

        return $this->json(['success' => true]);
    }
}
