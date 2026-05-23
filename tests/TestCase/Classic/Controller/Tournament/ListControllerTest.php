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
                static::assertCount(2, $rows);

                // Spring Cup first (ORDER BY startedAt DESC: 2025-03 > 2024-10)
                $firstRow = $rows->eq(0);
                static::assertStringContainsString('Весняний кубок', $firstRow->filter('td')->eq(0)->text());
                static::assertStringContainsString('01.03.2025', $firstRow->filter('td')->eq(1)->text());
                static::assertStringContainsString('3.5', $firstRow->filter('td')->eq(2)->text());
                static::assertStringContainsString('2.8', $firstRow->filter('td')->eq(3)->text());
                // teamCount: 3 teams (alpha, beta, gamma) across 2 sessions (calculated)
                $firstRow->filter('td')->eq(4)->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('3', $x));

                $secondRow = $rows->eq(1);
                static::assertStringContainsString('Осінній бриз', $secondRow->filter('td')->eq(0)->text());
                static::assertStringContainsString('4.2', $secondRow->filter('td')->eq(2)->text());
                // teamCount: 1 team (alpha) in 1 session (calculated)
                $secondRow->filter('td')->eq(4)->text()
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

        yield 'POST not allowed' => [
            'method' => 'POST',
            'uri' => '/tournaments/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 405,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];
    }
}
