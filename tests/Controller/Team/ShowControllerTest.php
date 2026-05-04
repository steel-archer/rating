<?php

declare(strict_types=1);

namespace App\Tests\Controller\Team;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'show team with squad, captain and tournaments' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/team/' . $objects['team_alpha']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                // name and town in h1
                static::assertSelectorTextContains('h1', 'Альфа');
                static::assertSelectorTextContains('h1', 'Київ');

                // squad: Shevchenko (captain) and Lesya
                $card = $crawler->filter('.card');
                static::assertStringContainsString('Шевченко', $card->text());
                static::assertStringContainsString('Українка', $card->text());
                static::assertStringContainsString('(к)', $card->text());

                // season name
                static::assertStringContainsString('2025-2026', $card->text());

                // turbo-frame for tournaments (calculated tournamentCount > 0)
                $frame = $crawler->filter('turbo-frame#team-tournaments');
                static::assertCount(1, $frame);
                static::assertStringContainsString('/tournaments', $frame->attr('src'));
            },
        ];

        yield 'show team without squad' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/team/' . $objects['team_gamma']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertSelectorTextContains('h1', 'Гамма');
                // no squad section (no TeamPlayer for Gamma)
                static::assertStringNotContainsString('(к)', $crawler->text());
            },
        ];

        yield 'not found for non-existent team' => [
            'method' => 'GET',
            'uri' => '/team/999999',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'not found for non-numeric id' => [
            'method' => 'GET',
            'uri' => '/team/abc',
            'fixtures' => [],
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];
    }
}
