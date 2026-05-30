<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\Player;

use App\Classic\Service\PlayerService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ShowControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testShow(
        string $method,
        string|callable $uri,
        array $fixtures,
        ?string $loginAs,
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
            $mockSetup($this, $client, $objects);
        }

        $resolvedUri = is_callable($uri) ? $uri($objects) : $uri;
        $crawler = $client->request($method, $resolvedUri);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($crawler, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'anonymous gets redirected' => [
            'method' => 'GET',
            'uri' => '/player/1',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'show player with squad and tournaments' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_shevchenko']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertSelectorTextContains('h1', 'Шевченко Тарас Григорович');

                // town in meta
                static::assertStringContainsString('Київ', $crawler->filter('.meta')->text());

                // squad: team Alpha in season 2024-2025
                $card = $crawler->filter('.card');
                static::assertStringContainsString('Альфа', $card->text());
                static::assertStringContainsString('2025-2026', $card->text());

                // captain marker
                static::assertStringContainsString('(к)', $card->text());

                // turbo-frame for tournaments (calculated tournamentCount > 0)
                $frame = $crawler->filter('turbo-frame#player-tournaments');
                static::assertCount(1, $frame);
                static::assertStringContainsString('/tournaments', $frame->attr('src'));
            },
        ];

        yield 'show player without patronymic' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_lesya']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertSelectorTextContains('h1', 'Українка Леся');
            },
        ];

        yield 'not found for non-existent player' => [
            'method' => 'GET',
            'uri' => '/player/999999',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'not found for non-numeric id' => [
            'method' => 'GET',
            'uri' => '/player/abc',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'service unavailable on throwable' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_shevchenko']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, $client, $objects) {
                $client->disableReboot();
                $stub = $test->createStub(PlayerService::class);
                $stub->method('get')->willThrowException(new RuntimeException('DB down'));
                static::getContainer()->set(PlayerService::class, $stub);
            },
        ];

        yield 'owner sees contacts section' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_shevchenko']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertCount(1, $crawler->filter('#player-contacts'));
                static::assertCount(1, $crawler->filter('#contacts-form'));
            },
            'mockSetup' => static function (self $test, $client, $objects) {
                $client->loginUser($objects['user_with_player']);
            },
        ];

        yield 'moderator sees contacts read-only' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_shevchenko']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertCount(1, $crawler->filter('#player-contacts'));
                static::assertCount(0, $crawler->filter('#contacts-form'));
                static::assertStringContainsString('player@example.com', $crawler->filter('#player-contacts')->text());
            },
            'mockSetup' => static function (self $test, $client, $objects) {
                $client->loginUser($objects['user_moderator']);
            },
        ];

        yield 'user without player gets redirected to player-claim' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_shevchenko']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 302,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'blocked user notice is visible to everyone' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_lesya']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/user_blocked.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertStringContainsString('Цього користувача заблоковано', $crawler->filter('.flash-error')->text());
            },
        ];

        yield 'no blocked notice for active user' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_shevchenko']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertCount(0, $crawler->filter('.flash-error'));
            },
        ];
    }
}
