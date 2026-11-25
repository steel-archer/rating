<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\Tournament\Document;

use App\Classic\Entity\TournamentDocumentDownload;
use App\Classic\Enum\TournamentDocumentVisibility;
use App\Tests\FixturesTrait;
use App\Tests\TestCase\Classic\Controller\My\Tournament\Document\DocumentTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DownloadOpenDocumentControllerTest extends WebTestCase
{
    use DocumentTestTrait;
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/users.yaml',
        'Entity/my_tournaments.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testDownloadOpenDocument(
        array $fixtures,
        ?string $loginAs,
        string $tournamentKey,
        TournamentDocumentVisibility $visibility,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);
        $documentId = self::createDocumentDirectly($objects[$tournamentKey]->getId(), $visibility);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request('GET', '/tournament/documents/' . $documentId . '/download');

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'any player downloads open document of published tournament' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_with_player',
            'tournamentKey' => 'tournament_published',
            'visibility' => TournamentDocumentVisibility::Open,
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringContainsString(
                    'attachment',
                    $client->getResponse()->headers->get('content-disposition'),
                );
                // Open downloads are not logged.
                static::assertCount(
                    0,
                    static::getContainer()->get('doctrine')
                        ->getRepository(TournamentDocumentDownload::class)
                        ->findAll(),
                );
            },
        ];

        yield 'organizer downloads open document of draft tournament' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_creator',
            'tournamentKey' => 'tournament_draft',
            'visibility' => TournamentDocumentVisibility::Open,
            'expectedStatus' => 200,
            'afterCallback' => static function () {
            },
        ];

        yield 'non-organizer cannot download open document of draft tournament' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_with_player',
            'tournamentKey' => 'tournament_draft',
            'visibility' => TournamentDocumentVisibility::Open,
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'closed document is not served by the open route' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_creator',
            'tournamentKey' => 'tournament_published',
            'visibility' => TournamentDocumentVisibility::Closed,
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'anonymous is redirected to login' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'tournamentKey' => 'tournament_published',
            'visibility' => TournamentDocumentVisibility::Open,
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];
    }
}
