<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\Tournament;

use App\Entity\TournamentDocument;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UploadDocumentControllerTest extends WebTestCase
{
    use DocumentTestTrait;
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/users.yaml',
        'Entity/my_tournaments.yaml',
    ];

    private const array FIXTURES_STARTED = [
        'Entity/base.yaml',
        'Entity/users.yaml',
        'Entity/my_tournaments_started.yaml',
    ];

    /**
     * @param list<string> $fixtures
     * @throws \JsonException
     */
    #[DataProvider('dataProvider')]
    public function testUploadDocument(
        array $fixtures,
        ?string $loginAs,
        callable $action,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $action($client, $objects);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'upload success' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId() . '/documents',
                [],
                ['file' => self::createTestPdf()],
            ),
            'expectedStatus' => 201,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($json['success']);
                static::assertNotEmpty($json['document']['id']);

                $document = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentDocument::class)
                    ->find($json['document']['id']);
                static::assertNotNull($document);
            },
        ];

        yield 'upload to published not-started tournament succeeds' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_published']->getId() . '/documents',
                [],
                ['file' => self::createTestPdf()],
            ),
            'expectedStatus' => 201,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($json['success']);
            },
        ];

        yield 'upload denied for started tournament' => [
            'fixtures' => self::FIXTURES_STARTED,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_started']->getId() . '/documents',
                [],
                ['file' => self::createTestPdf()],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('tournament.document.error.started', $json['error']);
            },
        ];

        yield 'upload denied for non-organizer' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId() . '/documents',
                [],
                ['file' => self::createTestPdf()],
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'upload without file returns 422' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId() . '/documents',
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('tournament.document.error.no_file', $json['error']);
            },
        ];

        yield 'upload invalid type returns 422' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId() . '/documents',
                [],
                ['file' => self::createTestFile('test.txt', 'text/plain')],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('tournament.document.error.invalid_type', $json['error']);
            },
        ];

        yield 'upload requires login' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId() . '/documents',
                [],
                ['file' => self::createTestPdf()],
            ),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'upload max files limit' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_creator',
            'action' => static function (KernelBrowser $client, array $objects): void {
                $url = '/my/tournaments/' . $objects['tournament_draft']->getId() . '/documents';
                for ($i = 0; $i < 3; $i++) {
                    $client->request('POST', $url, [], ['file' => self::createTestPdf()]);
                }
                $client->request('POST', $url, [], ['file' => self::createTestPdf()]);
            },
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('tournament.document.error.max_files', $json['error']);
            },
        ];
    }
}
