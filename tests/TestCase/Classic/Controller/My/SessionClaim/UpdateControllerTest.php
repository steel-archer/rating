<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\SessionClaim;

use App\Classic\Entity\TournamentSession;
use App\Classic\Service\SessionClaimService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UpdateControllerTest extends WebTestCase
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
    public function testUpdate(
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
        yield 'update successfully' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_pending']->getId() . '/update',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'playedAt' => '2025-06-18',
                    'estimatedTeams' => 12,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $session = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentSession::class)
                    ->find($objects['session_pending']->getId());
                static::assertSame('2025-06-18', $session->getPlayedAt()->format('Y-m-d'));
                static::assertSame(12, $session->getEstimatedTeams());
            },
        ];

        yield 'update with date out of range' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_pending']->getId() . '/update',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'playedAt' => '2025-07-15',
                    'estimatedTeams' => 12,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('session_claim.error.date_after_end', $body['error']);
            },
        ];

        yield 'update by non-owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_other',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_pending']->getId() . '/update',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['estimatedTeams' => 12], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'update with host' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_pending']->getId() . '/update',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'playedAt' => '2025-06-10',
                    'estimatedTeams' => 8,
                    'hostId' => $objects['player_lesya']->getId(),
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $session = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentSession::class)
                    ->find($objects['session_pending']->getId());
                static::assertSame($objects['player_lesya']->getId(), $session->getHost()->getId());
            },
        ];

        yield 'update clears host when hostId is null' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_pending']->getId() . '/update',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'playedAt' => '2025-06-10',
                    'estimatedTeams' => 8,
                    'hostId' => null,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $session = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentSession::class)
                    ->find($objects['session_pending']->getId());
                static::assertNull($session->getHost());
            },
        ];

        yield 'update clears date when playedAt is null' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_pending']->getId() . '/update',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'playedAt' => null,
                    'estimatedTeams' => 8,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $session = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentSession::class)
                    ->find($objects['session_pending']->getId());
                static::assertNull($session->getPlayedAt());
            },
        ];

        yield 'registration closed' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims_expired.yaml'],
            'loginAs' => 'user_representative_exp',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_expired_approved']->getId() . '/update',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'estimatedTeams' => 12,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('session_claim.error.registration_closed', $body['error']);
            },
        ];

        yield 'throwable returns 500' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_pending']->getId() . '/update',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['estimatedTeams' => 8], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('common.error', $body['error']);
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(SessionClaimService::class);
                $stub->method('update')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(SessionClaimService::class, $stub);
            },
        ];
    }
}
