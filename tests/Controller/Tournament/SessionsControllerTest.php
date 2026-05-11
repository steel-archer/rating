<?php

declare(strict_types=1);

namespace App\Tests\Controller\Tournament;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
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
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
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
        yield 'anonymous gets redirected' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'uri' => '/tournament/1/sessions',
            'expectedStatus' => 302,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'sessions page renders tournament name' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_spring']->getId() . '/sessions',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertSelectorTextContains('h1', 'Весняний кубок');
                $frame = $crawler->filter('turbo-frame#tournament-sessions');
                static::assertCount(1, $frame);
                static::assertStringContainsString('/sessions/list', $frame->attr('src'));
            },
        ];

        yield 'not found for non-existent tournament' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'uri' => '/tournament/999999/sessions',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'shows submit link for representative' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims.yaml'],
            'loginAs' => 'user_representative',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_session_test']->getId() . '/sessions',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $link = $crawler->filter('a[href*="/my/session-claims/create/"]');
                static::assertCount(1, $link);
            },
        ];

        yield 'no submit link for non-representative' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims.yaml'],
            'loginAs' => 'user_other',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_session_test']->getId() . '/sessions',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $link = $crawler->filter('a[href*="/my/session-claims/create/"]');
                static::assertCount(0, $link);
            },
        ];
    }
}
