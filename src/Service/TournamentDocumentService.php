<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Tournament;
use App\Entity\TournamentDocument;
use App\Repository\TournamentDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class TournamentDocumentService
{
    private const int MAX_FILES = 3;
    private const int MAX_SIZE_BYTES = 10 * 1024 * 1024;

    private const array ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.text',
    ];

    private const array ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'odt'];

    public function __construct(
        private EntityManagerInterface $em,
        private TournamentDocumentRepository $documentRepository,
        private SluggerInterface $slugger,
        private Filesystem $filesystem,
        private string $uploadDir,
    ) {
    }

    /**
     * @throws LogicException
     */
    public function upload(Tournament $tournament, UploadedFile $file): TournamentDocument
    {
        $count = $this->documentRepository->countByTournament($tournament);
        if ($count >= self::MAX_FILES) {
            throw new LogicException('tournament.document.error.max_files');
        }

        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            throw new LogicException('tournament.document.error.too_large');
        }

        $mimeType = $file->getMimeType() ?? '';
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new LogicException('tournament.document.error.invalid_type');
        }

        $originalName = mb_substr(trim($file->getClientOriginalName()), 0, 200);
        $safeFilename = $this->slugger->slug(
            pathinfo($originalName, PATHINFO_FILENAME),
        );
        $extension = $file->guessExtension() ?? pathinfo($originalName, PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), self::ALLOWED_EXTENSIONS, true)) {
            throw new LogicException('tournament.document.error.invalid_type');
        }
        $storedName = $safeFilename . '-' . uniqid() . '.' . $extension;
        $fileSize = $file->getSize();

        if ($fileSize === false || $fileSize === 0) {
            throw new LogicException('tournament.document.error.no_file');
        }

        $tournamentDir = $this->getDirectory($tournament);
        $file->move($tournamentDir, $storedName);

        $document = new TournamentDocument();
        $document->setTournament($tournament);
        $document->setOriginalName($originalName);
        $document->setStoredName($storedName);
        $document->setMimeType($mimeType);
        $document->setSize($fileSize);

        $this->em->persist($document);
        $this->em->flush();

        return $document;
    }

    public function delete(TournamentDocument $document): void
    {
        $path = $this->getFilePath($document);
        if ($this->filesystem->exists($path)) {
            $this->filesystem->remove($path);
        }

        $this->em->remove($document);
        $this->em->flush();
    }

    public function deleteAllByTournament(Tournament $tournament): void
    {
        $documents = $this->documentRepository->findByTournament($tournament);

        foreach ($documents as $document) {
            $path = $this->getFilePath($document);
            if ($this->filesystem->exists($path)) {
                $this->filesystem->remove($path);
            }
            $this->em->remove($document);
        }

        $tournamentDir = $this->getDirectory($tournament);
        if ($this->filesystem->exists($tournamentDir)) {
            $this->filesystem->remove($tournamentDir);
        }
    }

    public function getFilePath(TournamentDocument $document): string
    {
        return $this->getDirectory($document->getTournament())
            . '/' . $document->getStoredName();
    }

    private function getDirectory(Tournament $tournament): string
    {
        return $this->uploadDir . '/' . $tournament->getId();
    }
}
