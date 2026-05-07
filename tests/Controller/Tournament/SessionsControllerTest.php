<?php

declare(strict_types=1);

namespace App\Tests\Controller\Tournament;

use App\Service\SessionClaimService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class SessionsControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testSessions(
        array $fixtures,
        ?string $loginAs,
        string|callable $uri,
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

        $resolvedUri = is_callable($uri) ? $uri($objects) : $uri;
        $crawler = $client->request('GET', $resolvedUri);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($crawler, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'sessions page renders tournament name' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'loginAs' => null,
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_spring']->getId() . '/sessions',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler) {
                static::assertSelectorTextContains('h1', 'Весняний кубок');
                $frame = $crawler->filter('turbo-frame#tournament-sessions');
                static::assertCount(1, $frame);
                static::assertStringContainsString('/sessions/list', $frame->attr('src'));
            },
        ];

        yield 'not found for non-existent tournament' => [
            'fixtures' => ['Entity/base.yaml'],
            'loginAs' => null,
            'uri' => '/tournament/999999/sessions',
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'shows claim form for representative' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims.yaml'],
            'loginAs' => 'user_representative',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_session_test']->getId() . '/sessions',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler) {
                static::assertCount(1, $crawler->filter('#session-claim-form-card'));
            },
        ];

        yield 'organizer sees pending claims' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims.yaml'],
            'loginAs' => 'user_organizer',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_session_test']->getId() . '/sessions',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler) {
                static::assertCount(1, $crawler->filter('#session-claims-list'));
                $rows = $crawler->filter('#session-claims-list table tbody tr');
                static::assertGreaterThanOrEqual(1, $rows->count());
            },
        ];

        yield 'representative sees own claims' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims.yaml'],
            'loginAs' => 'user_representative',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_session_test']->getId() . '/sessions',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler) {
                static::assertCount(1, $crawler->filter('#session-claims-list'));
            },
        ];

        yield 'no claim form for non-representative' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims.yaml'],
            'loginAs' => 'user_other',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_session_test']->getId() . '/sessions',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler) {
                static::assertCount(0, $crawler->filter('#session-claim-form-card'));
            },
        ];

        yield 'service unavailable on throwable' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims.yaml'],
            'loginAs' => 'user_representative',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_session_test']->getId() . '/sessions',
            'expectedStatus' => 503,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(SessionClaimService::class);
                $stub->method('isOrganizer')->willThrowException(new RuntimeException('DB down'));
                static::getContainer()->set(SessionClaimService::class, $stub);
            },
        ];
    }
}
