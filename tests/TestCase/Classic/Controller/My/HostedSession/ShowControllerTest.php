<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\HostedSession;

use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentDocument;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ShowControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/session_claims.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testShow(
        array $fixtures,
        string $loginAs,
        string $sessionRef,
        bool $createDocument,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($createDocument) {
            self::createDocumentDirectly($objects['tournament_session_test']->getId());
        }

        $client->loginUser($objects[$loginAs]);
        $client->request('GET', '/my/hosted-sessions/' . $objects[$sessionRef]->getId());

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        // session_approved: host = player_shevchenko (user_representative), claim approved.
        yield 'host of approved session sees the package' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'sessionRef' => 'session_approved',
            'createDocument' => true,
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                static::assertCount(1, $crawler->filter('a.document-link'));
            },
        ];

        yield 'host of approved session without package sees empty notice' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'sessionRef' => 'session_approved',
            'createDocument' => false,
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                static::assertCount(0, $crawler->filter('a.document-link'));
                static::assertCount(1, $crawler->filter('.empty-state'));
            },
        ];

        // Package access is tournament-scoped: session_pending is not itself approved,
        // but the host also has an approved session (session_approved) in the same
        // tournament, so the package is reachable through the pending session too.
        yield 'host reaches package via pending session when tournament has approved one' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'sessionRef' => 'session_pending',
            'createDocument' => false,
            'expectedStatus' => 200,
            'afterCallback' => static function () {
            },
        ];

        yield 'non-host player gets 404' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_other',
            'sessionRef' => 'session_approved',
            'createDocument' => false,
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        // A host whose tournament has no approved session of theirs must not reach the package.
        yield 'host without approved session in tournament gets 404' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/hosted_session_no_approved.yaml'],
            'loginAs' => 'user_host_no_approved',
            'sessionRef' => 'session_host_pending',
            'createDocument' => false,
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];
    }

    private static function createDocumentDirectly(int $tournamentId): void
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
    }
}
