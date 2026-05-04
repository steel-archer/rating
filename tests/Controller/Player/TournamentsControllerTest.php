<?php

declare(strict_types=1);

namespace App\Tests\Controller\Player;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class TournamentsControllerTest extends WebTestCase
{
    use FixturesTrait;

    #[DataProvider('dataProvider')]
    public function testTournaments(
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
        yield 'player tournaments with calculated places and legionary flag' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_shevchenko']->getId() . '/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                // Shevchenko plays in: spring(Альфа), spring(Гамма as legionary), autumn(... not in stp fixtures)
                // From fixtures: stp_spring_alpha_shevchenko + stp_spring_gamma_shevchenko = 2 appearances
                static::assertGreaterThanOrEqual(2, $rows->count());

                // check tournament names present
                $texts = $rows->each(fn(Crawler $row) => $row->text());
                $allText = implode(' ', $texts);
                static::assertStringContainsString('Весняний кубок', $allText);

                // legionary marker present (Гамма appearance)
                static::assertStringContainsString('не в складі', $allText);

                // scores present
                static::assertStringContainsString('25', $allText);
                static::assertStringContainsString('20', $allText);
            },
        ];

        yield 'player with no tournament appearances returns empty table' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_franko']->getId() . '/tournaments',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(0, $rows);
            },
        ];

        yield 'not found for non-existent player' => [
            'method' => 'GET',
            'uri' => '/player/999999/tournaments',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {},
        ];
    }
}
