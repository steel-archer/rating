<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\Tournament\Document;

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
}
