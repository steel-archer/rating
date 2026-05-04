<?php

declare(strict_types=1);

namespace App\Tests\Controller\Player;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ShowControllerTest extends WebTestCase
{
    use FixturesTrait;

    #[DataProvider('dataProvider')]
    public function testShow(
        string $method,
        string|callable $uri,
        array $fixtures,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        $resolvedUri = is_callable($uri) ? $uri($objects) : $uri;
        $crawler = $client->request($method, $resolvedUri);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($crawler, $objects);
    }

    public static function dataProvider(): iterable
    {
        yield 'show player with squad and tournaments' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_shevchenko']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertSelectorTextContains('h1', 'Шевченко Тарас Григорович');

                // town in meta
                static::assertStringContainsString('Київ', $crawler->filter('.meta')->text());

                // squad: team Альфа in season 2024-2025
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
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertSelectorTextContains('h1', 'Українка Леся');
            },
        ];

        yield 'not found for non-existent player' => [
            'method' => 'GET',
            'uri' => '/player/999999',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {},
        ];

        yield 'not found for non-numeric id' => [
            'method' => 'GET',
            'uri' => '/player/abc',
            'fixtures' => [],
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {},
        ];
    }
}
