<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\Tournament\Document;

use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentDocument;
use App\Classic\Enum\TournamentDocumentVisibility;
use JsonException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait DocumentTestTrait
{
    private static function createTestPdf(): UploadedFile
    {
        $projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $tmp = tempnam(sys_get_temp_dir(), 'pdf_');
        copy($projectDir . '/tests/Fixtures/Files/test.pdf', $tmp);

        return new UploadedFile($tmp, 'document.pdf', 'application/pdf', null, true);
    }

    private static function createTestFile(string $name, string $mimeType): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmp, 'test content');

        return new UploadedFile($tmp, $name, $mimeType, null, true);
    }

    /**
     * @param array<string, object> $objects
     * @throws JsonException
     */
    private static function uploadDocumentAs(
        KernelBrowser $client,
        array $objects,
        string $userKey,
        string $tournamentKey,
    ): int {
        $client->loginUser($objects[$userKey]);
        $client->request(
            'POST',
            '/my/tournaments/' . $objects[$tournamentKey]->getId() . '/documents',
            [],
            ['file' => self::createTestPdf()],
        );

        $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $json['document']['id'];
    }

    private static function createDocumentDirectly(
        int $tournamentId,
        TournamentDocumentVisibility $visibility = TournamentDocumentVisibility::Closed,
    ): int {
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $tournament = $em->getRepository(Tournament::class)->find($tournamentId);

        $projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $uploadDir = static::getContainer()->getParameter('app.upload_dir');
        $dir = $uploadDir . '/' . $tournamentId;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $storedName = 'test-' . uniqid() . '.pdf';
        copy($projectDir . '/tests/Fixtures/Files/test.pdf', $dir . '/' . $storedName);

        $document = new TournamentDocument();
        $document->setTournament($tournament);
        $document->setOriginalName('test.pdf');
        $document->setStoredName($storedName);
        $document->setMimeType('application/pdf');
        $document->setSize(filesize($dir . '/' . $storedName));
        $document->setVisibility($visibility);

        $em->persist($document);
        $em->flush();

        return $document->getId();
    }
}
