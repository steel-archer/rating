<?php

declare(strict_types=1);

namespace App\Tests\Controller\Tournament;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ResultsControllerTest extends WebTestCase
{
    use FixturesTrait;

    #[DataProvider('dataProvider')]
    public function testResults(
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
        yield 'results sorted by score desc with calculated places' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_spring']->getId() . '/results',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                // 3 teams: beta(30), alpha(25), gamma(20)
                static::assertCount(3, $rows);

                // 1st place: Бета, score 30
                $first = $rows->eq(0);
                $first->filter('td')->eq(0)->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('1', $x));
                static::assertStringContainsString('Бета', $first->filter('td')->eq(1)->text());
                static::assertStringContainsString('30', $first->filter('td')->eq(3)->text());

                // 2nd place: Альфа, score 25
                $second = $rows->eq(1);
                $second->filter('td')->eq(0)->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('2', $x));
                static::assertStringContainsString('Альфа', $second->filter('td')->eq(1)->text());
                static::assertStringContainsString('25', $second->filter('td')->eq(3)->text());

                // 3rd place: Гамма, score 20
                $third = $rows->eq(2);
                $third->filter('td')->eq(0)->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('3', $x));
                static::assertStringContainsString('Гамма', $third->filter('td')->eq(1)->text());
                static::assertStringContainsString('20', $third->filter('td')->eq(3)->text());

                // town displayed
                static::assertStringContainsString('Львів', $first->filter('td')->eq(2)->text());
                static::assertStringContainsString('Київ', $second->filter('td')->eq(2)->text());
            },
        ];

        yield 'results for tournament with single team' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_autumn']->getId() . '/results',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                $rows->eq(0)->filter('td')->eq(0)->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('1', $x));
                static::assertStringContainsString('Альфа', $rows->eq(0)->filter('td')->eq(1)->text());
                static::assertStringContainsString('40', $rows->eq(0)->filter('td')->eq(3)->text());
            },
        ];

        yield 'results empty for tournament without teams' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_empty']->getId() . '/results',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournament_empty.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(0, $rows);
            },
        ];

        yield 'not found for non-existent tournament' => [
            'method' => 'GET',
            'uri' => '/tournament/999999/results',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {},
        ];
    }
}
