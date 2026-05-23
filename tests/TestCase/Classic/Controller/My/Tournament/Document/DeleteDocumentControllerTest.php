<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\Tournament\Document;

use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentDocument;
use App\Classic\Enum\TournamentStatus;
use App\Tests\FixturesTrait;
use DateTimeImmutable;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DeleteDocumentControllerTest extends WebTestCase
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
     * @throws JsonException
     */
    #[DataProvider('dataProvider')]
    public function testDeleteDocument(
        array $fixtures,
        string $uploadAs,
        string $deleteAs,
        callable $getTournamentId,
        int $expectedStatus,
        callable $afterCallback,
        ?callable $beforeDelete = null,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        $tournamentId = $getTournamentId($objects);
        $documentId = self::uploadDocumentAs($client, $objects, $uploadAs, $tournamentId);

        if ($beforeDelete !== null) {
            $beforeDelete($tournamentId);
        }

        $client->loginUser($objects[$deleteAs]);
        $client->request('DELETE', '/my/tournaments/documents/' . $documentId);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $documentId);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'delete success' => [
            'fixtures' => self::FIXTURES,
            'uploadAs' => 'user_creator',
            'deleteAs' => 'user_creator',
            'getTournamentId' => static fn(array $objects) => $objects['tournament_draft']->getId(),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, int $documentId) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($json['success']);

                $document = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentDocument::class)
                    ->find($documentId);
                static::assertNull($document);
            },
        ];

        yield 'delete denied for non-organizer' => [
            'fixtures' => self::FIXTURES,
            'uploadAs' => 'user_creator',
            'deleteAs' => 'user_with_player',
            'getTournamentId' => static fn(array $objects) => $objects['tournament_draft']->getId(),
            'expectedStatus' => 404,
            'afterCallback' => static function (KernelBrowser $client, int $documentId) {
                $document = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentDocument::class)
                    ->find($documentId);
                static::assertNotNull($document);
            },
        ];

        yield 'delete denied for started tournament' => [
            'fixtures' => self::FIXTURES,
            'uploadAs' => 'user_creator',
            'deleteAs' => 'user_creator',
            'getTournamentId' => static fn(array $objects) => $objects['tournament_draft']->getId(),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('tournament.document.error.started', $json['error']);
            },
            'beforeDelete' => static function (int $tournamentId): void {
                $em = static::getContainer()->get('doctrine.orm.entity_manager');
                $tournament = $em->getRepository(Tournament::class)->find($tournamentId);
                $tournament->setStatus(TournamentStatus::Published);
                $tournament->setStartedAt(new DateTimeImmutable('-1 day'));
                $em->flush();
            },
        ];
    }

    /**
     * @param array<string, object> $objects
     * @throws JsonException
     */
    private static function uploadDocumentAs(
        KernelBrowser $client,
        array $objects,
        string $userKey,
        int $tournamentId,
    ): int {
        $client->loginUser($objects[$userKey]);
        $client->request(
            'POST',
            '/my/tournaments/' . $tournamentId . '/documents',
            [],
            ['file' => self::createTestPdf()],
        );

        $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $json['document']['id'];
    }
}
