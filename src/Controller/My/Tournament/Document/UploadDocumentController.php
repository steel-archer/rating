<?php

declare(strict_types=1);

namespace App\Controller\My\Tournament\Document;

use App\Attribute\RateLimited;
use App\DTO\Response\My\UploadedDocumentDTO;
use App\Entity\Tournament;
use App\Mapping\Mapper;
use App\Security\TournamentOrganizerVoter;
use App\Service\TournamentDocumentService;
use LogicException;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/tournaments/{id}/documents', name: 'my_tournament_document_upload', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('upload')]
class UploadDocumentController extends AbstractController
{
    /**
     * @throws RandomException
     */
    public function __invoke(
        Tournament $tournament,
        Request $request,
        TournamentDocumentService $service,
        Mapper $mapper,
    ): JsonResponse {
        if (!$this->isGranted(TournamentOrganizerVoter::EDIT, $tournament)) {
            return $this->json(['error' => 'common.not_found'], 404);
        }

        if ($tournament->isStarted()) {
            return $this->json(['error' => 'tournament.document.error.started'], 422);
        }

        $file = $request->files->get('file');
        if ($file === null) {
            return $this->json(['error' => 'tournament.document.error.no_file'], 422);
        }

        try {
            $document = $service->upload($tournament, $file);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json([
            'success' => true,
            'document' => $mapper->map($document, UploadedDocumentDTO::class),
        ], 201);
    }
}
