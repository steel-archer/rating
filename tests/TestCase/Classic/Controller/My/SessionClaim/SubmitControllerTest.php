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

class SubmitControllerTest extends WebTestCase
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
    public function testSubmit(
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
        yield 'submit successfully' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['tournament_session_test']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'venueId' => $objects['venue_kyiv']->getId(),
                    'playedAt' => '2025-06-12',
                    'estimatedTeams' => 6,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $sessions = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentSession::class)
                    ->findBy(['tournament' => $objects['tournament_session_test']->getId()]);
                static::assertCount(5, $sessions);
            },
        ];

        yield 'submit without date' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['tournament_session_test']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'venueId' => $objects['venue_kyiv']->getId(),
                    'estimatedTeams' => 6,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function () {
            },
        ];

        yield 'date before tournament start' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['tournament_session_test']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'venueId' => $objects['venue_kyiv']->getId(),
                    'playedAt' => '2025-05-01',
                    'estimatedTeams' => 6,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('session_claim.error.date_before_start', $body['error']);
            },
        ];

        yield 'date after tournament end' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['tournament_session_test']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'venueId' => $objects['venue_kyiv']->getId(),
                    'playedAt' => '2025-07-15',
                    'estimatedTeams' => 6,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('session_claim.error.date_after_end', $body['error']);
            },
        ];

        yield 'non-representative' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_other',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['tournament_session_test']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'venueId' => $objects['venue_kyiv']->getId(),
                    'playedAt' => '2025-06-12',
                    'estimatedTeams' => 6,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('session_claim.error.not_representative', $body['error']);
            },
        ];

        yield 'registration closed' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims_expired.yaml'],
            'loginAs' => 'user_representative_exp',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['tournament_expired']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'venueId' => $objects['venue_kyiv']->getId(),
                    'playedAt' => (new \DateTimeImmutable('-45 days'))->format('Y-m-d'),
                    'estimatedTeams' => 6,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('session_claim.error.registration_closed', $body['error']);
            },
        ];

        yield 'non-existent tournament' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/session-claims/999999/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['venueId' => 1, 'estimatedTeams' => 6], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'non-existent venue' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['tournament_session_test']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['venueId' => 999999, 'estimatedTeams' => 6], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('common.not_found', $body['error']);
            },
        ];

        yield 'submit with host' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['tournament_session_test']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'venueId' => $objects['venue_kyiv']->getId(),
                    'playedAt' => '2025-06-12',
                    'estimatedTeams' => 6,
                    'hostId' => $objects['player_lesya']->getId(),
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $sessions = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentSession::class)
                    ->findBy(['host' => $objects['player_lesya']->getId()]);
                static::assertNotEmpty($sessions);
            },
        ];

        yield 'submit without estimatedTeams' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['tournament_session_test']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'venueId' => $objects['venue_kyiv']->getId(),
                    'playedAt' => '2025-06-12',
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function () {
            },
        ];

        yield 'validation error for negative estimatedTeams' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['tournament_session_test']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'venueId' => $objects['venue_kyiv']->getId(),
                    'estimatedTeams' => -1,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'throwable returns 500' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['tournament_session_test']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'venueId' => $objects['venue_kyiv']->getId(),
                    'estimatedTeams' => 6,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('common.error', $body['error']);
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(SessionClaimService::class);
                $stub->method('submit')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(SessionClaimService::class, $stub);
            },
        ];

        yield 'submit offline venue with isOnline true' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['tournament_session_test']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'venueId' => $objects['venue_kyiv']->getId(),
                    'playedAt' => '2025-06-13',
                    'estimatedTeams' => 4,
                    'isOnline' => true,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function () {
                $sessions = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentSession::class)
                    ->findBy(['isOnline' => true]);
                static::assertNotEmpty($sessions);
                $session = $sessions[array_key_last($sessions)];
                static::assertTrue($session->isOnline());
            },
        ];

        yield 'submit online venue sets isOnline automatically' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['tournament_session_test']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'venueId' => $objects['venue_online_sc']->getId(),
                    'playedAt' => '2025-06-14',
                    'estimatedTeams' => 3,
                    'isOnline' => false,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $sessions = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentSession::class)
                    ->findBy(['venue' => $objects['venue_online_sc']->getId()]);
                static::assertNotEmpty($sessions);
                static::assertTrue($sessions[0]->isOnline());
            },
        ];

        yield 'submit offline venue defaults to offline session' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['tournament_session_test']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'venueId' => $objects['venue_kyiv']->getId(),
                    'playedAt' => '2025-06-15',
                    'estimatedTeams' => 5,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $sessions = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentSession::class)
                    ->findBy(['venue' => $objects['venue_kyiv']->getId()]);
                $lastSession = $sessions[array_key_last($sessions)];
                static::assertFalse($lastSession->isOnline());
            },
        ];
    }
}
