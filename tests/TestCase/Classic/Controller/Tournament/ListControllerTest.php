<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\Tournament;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Classic\Repository\TournamentRepository;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ListControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testList(
        string $method,
        string $uri,
        array $fixtures,
        ?string $loginAs,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $crawler = $client->request($method, $uri);

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
            'uri' => '/tournaments/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'list shows tournaments with calculated team counts' => [
            'method' => 'GET',
            'uri' => '/tournaments/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(3, $rows);

                // Spring Cup first (ORDER BY startedAt DESC: 2025-03 > 2025-02 > 2024-10)
                $firstRow = $rows->eq(0);
                static::assertStringContainsString('Весняний кубок', $firstRow->filter('td')->eq(0)->text());
                static::assertStringContainsString('01.03.2025', $firstRow->filter('td')->eq(3)->text());
                static::assertStringContainsString('3.5', $firstRow->filter('td')->eq(4)->text());
                static::assertStringContainsString('2.8', $firstRow->filter('td')->eq(5)->text());
                // teamCount: 3 teams (alpha, beta, gamma) across 2 sessions (calculated)
                $firstRow->filter('td')->eq(6)->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('3', $x));

                $thirdRow = $rows->eq(2);
                static::assertStringContainsString('Осінній бриз', $thirdRow->filter('td')->eq(0)->text());
                static::assertStringContainsString('4.2', $thirdRow->filter('td')->eq(4)->text());
                // teamCount: 1 team (alpha) in 1 session (calculated)
                $thirdRow->filter('td')->eq(6)->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('1', $x));
            },
        ];

        yield 'list filters by name' => [
            'method' => 'GET',
            'uri' => '/tournaments/list?name=%D0%92%D0%B5%D1%81%D0%BD',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                static::assertStringContainsString('Весняний кубок', $rows->eq(0)->text());
            },
        ];

        yield 'list empty when no tournaments' => [
            'method' => 'GET',
            'uri' => '/tournaments/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertCount(0, $crawler->filter('table tbody tr td a'));
            },
        ];

        yield 'filter by format=distributed shows only distributed' => [
            'method' => 'GET',
            'uri' => '/tournaments/list?format=distributed',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(2, $rows);
                $allText = $crawler->filter('table tbody')->text();
                static::assertStringContainsString('Весняний кубок', $allText);
                static::assertStringContainsString('Осінній бриз', $allText);
                static::assertStringNotContainsString('Фестиваль Одеса', $allText);
            },
        ];

        yield 'filter by format=centralized shows only centralized' => [
            'method' => 'GET',
            'uri' => '/tournaments/list?format=centralized',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                static::assertStringContainsString('Фестиваль Одеса', $rows->eq(0)->text());
            },
        ];

        yield 'POST not allowed' => [
            'method' => 'POST',
            'uri' => '/tournaments/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 405,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'filter by period=past shows only past tournaments' => [
            'method' => 'GET',
            'uri' => '/tournaments/list?period=past',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_period.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                static::assertStringContainsString('Зимовий кубок', $rows->eq(0)->text());
            },
        ];

        yield 'filter by period=active shows only active tournaments' => [
            'method' => 'GET',
            'uri' => '/tournaments/list?period=active',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_period.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                static::assertStringContainsString('Літній марафон', $rows->eq(0)->text());
            },
        ];

        yield 'filter by period=future shows only future tournaments' => [
            'method' => 'GET',
            'uri' => '/tournaments/list?period=future',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_period.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                static::assertStringContainsString('Осінній вітер', $rows->eq(0)->text());
            },
        ];

        yield 'filter by period combined with name' => [
            'method' => 'GET',
            'uri' => '/tournaments/list?period=past&name=%D0%97%D0%B8%D0%BC',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_period.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                static::assertStringContainsString('Зимовий кубок', $rows->eq(0)->text());
            },
        ];

        yield 'filter by period=active with non-matching name returns empty' => [
            'method' => 'GET',
            'uri' => '/tournaments/list?period=active&name=%D0%97%D0%B8%D0%BC',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_period.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertCount(0, $crawler->filter('table tbody tr td a'));
            },
        ];

        yield 'invalid period value is ignored gracefully' => [
            'method' => 'GET',
            'uri' => '/tournaments/list?period=invalid',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_period.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];
    }
}
