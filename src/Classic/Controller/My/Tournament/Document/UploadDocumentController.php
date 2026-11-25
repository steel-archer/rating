<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\Tournament\Document;

use App\Common\Attribute\RateLimited;
use App\Classic\DTO\Response\My\UploadedDocumentDTO;
use App\Classic\Entity\Tournament;
use App\Classic\Enum\TournamentDocumentVisibility;
use App\Common\Mapping\Mapper;
use App\Classic\Security\TournamentOrganizerVoter;
use App\Classic\Service\TournamentDocumentService;
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

        $visibility = TournamentDocumentVisibility::tryFrom(
            trim((string) $request->request->get('visibility', TournamentDocumentVisibility::Closed->value)),
        );
        if ($visibility === null) {
            return $this->json(['error' => 'tournament.document.error.invalid_visibility'], 422);
        }

        // The question package is frozen once the tournament starts; open materials stay editable.
        if ($visibility === TournamentDocumentVisibility::Closed && $tournament->isStarted()) {
            return $this->json(['error' => 'tournament.document.error.started'], 422);
        }

        $file = $request->files->get('file');
        // PHP drops the whole body when it exceeds post_max_size, so the file looks missing.
        // A file over upload_max_filesize arrives with an error code instead.
        if (
            $this->exceedsPostMaxSize($request)
            || ($file !== null && in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true))
        ) {
            return $this->json(['error' => 'tournament.document.error.too_large'], 422);
        }

        if ($file === null) {
            return $this->json(['error' => 'tournament.document.error.no_file'], 422);
        }

        try {
            $document = $service->upload($tournament, $file, $visibility);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json([
            'success' => true,
            'document' => $mapper->map($document, UploadedDocumentDTO::class),
        ], 201);
    }

    private function exceedsPostMaxSize(Request $request): bool
    {
        $limit = ini_parse_quantity((string) ini_get('post_max_size'));

        return $limit > 0 && (int) $request->server->get('CONTENT_LENGTH', 0) > $limit;
    }
}
