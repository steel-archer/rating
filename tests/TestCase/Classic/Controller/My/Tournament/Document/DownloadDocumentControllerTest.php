<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\Tournament\Document;

use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentDocument;
use App\Classic\Entity\TournamentDocumentDownload;
use App\Tests\FixturesTrait;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DownloadDocumentControllerTest extends WebTestCase
{
    use DocumentTestTrait;
    use FixturesTrait;

    private const array FIXTURES_WITH_SESSION = [
        'Entity/base.yaml',
        'Entity/session_claims.yaml',
    ];

    /**
     * @param list<string> $fixtures
     * @throws JsonException
     */
    #[DataProvider('dataProvider')]
    public function testDownloadDocument(
        array $fixtures,
        string $downloadAs,
        callable $getDocumentId,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        $documentId = $getDocumentId($client, $objects);

        $client->loginUser($objects[$downloadAs]);
        $client->request('GET', '/my/tournaments/documents/' . $documentId . '/download');

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        // session_approved: host = player_shevchenko (user_representative), claim approved.
        yield 'download as host of approved session' => [
            'fixtures' => self::FIXTURES_WITH_SESSION,
            'downloadAs' => 'user_representative',
            'getDocumentId' => static fn(KernelBrowser $client, array $objects) => self::createDocumentDirectly($objects['tournament_session_test']->getId()),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                static::assertStringContainsString(
                    'attachment',
                    $client->getResponse()->headers->get('content-disposition'),
                );

                // The download is recorded for audit.
                $downloads = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentDocumentDownload::class)
                    ->findAll();
                static::assertCount(1, $downloads);
                static::assertSame(
                    $objects['player_shevchenko']->getId(),
                    $downloads[0]->getPlayer()->getId(),
                );
            },
        ];

        // Organizer is no longer allowed to download the question package.
        yield 'download denied for organizer' => [
            'fixtures' => self::FIXTURES_WITH_SESSION,
            'downloadAs' => 'user_organizer',
            'getDocumentId' => static fn(KernelBrowser $client, array $objects) => self::createDocumentDirectly($objects['tournament_session_test']->getId()),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        // A player who is not the host (even with other roles) has no access.
        yield 'download denied for non-host player' => [
            'fixtures' => self::FIXTURES_WITH_SESSION,
            'downloadAs' => 'user_other',
            'getDocumentId' => static fn(KernelBrowser $client, array $objects) => self::createDocumentDirectly($objects['tournament_session_test']->getId()),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];
    }

    private static function createDocumentDirectly(int $tournamentId): int
    {
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

        $em->persist($document);
        $em->flush();

        return $document->getId();
    }
}
