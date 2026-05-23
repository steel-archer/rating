<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\SessionClaim;

use App\Classic\Service\SessionClaimService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DeleteControllerTest extends WebTestCase
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
    public function testDelete(
        array $fixtures,
        ?string $loginAs,
        callable $action,
        int $expectedStatus,
        callable $afterCallback,
        ?callable $mockSetup = null,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        if ($mockSetup !== null) {
            $mockSetup($this, $client);
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
        yield 'delete pending session' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_pending']->getId() . '/delete',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);
            },
        ];

        yield 'delete by non-owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_other',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_pending']->getId() . '/delete',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'delete non-existent session' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/session-claims/999999/delete',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'delete session with results' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_spring_kyiv']->getId() . '/delete',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('session_claim.error.has_results', $body['error']);
            },
        ];

        yield 'throwable returns 500' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_pending']->getId() . '/delete',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('common.error', $body['error']);
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(SessionClaimService::class);
                $stub->method('delete')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(SessionClaimService::class, $stub);
            },
        ];
    }
}
