<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\Tournament;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class DetailedResultsControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testDetailedResults(
        string $method,
        string|callable $uri,
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
            'uri' => '/tournament/1/detailed',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'hidden details - regular user gets 404' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_detailed']->getId() . '/detailed',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_detailed.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'hidden details - official sees breakdown' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_detailed']->getId() . '/detailed',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_detailed.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $table = $crawler->filter('table.results-breakdown');
                static::assertCount(1, $table);

                $rows = $table->filter('tbody tr');
                static::assertCount(2, $rows);

                // First team (alpha, score 5) should have correct answers marked
                $firstRow = $rows->eq(0);
                $correctCells = $firstRow->filter('td.answer-correct');
                static::assertCount(5, $correctCells);

                $wrongCells = $firstRow->filter('td.answer-wrong');
                static::assertCount(1, $wrongCells);
            },
        ];

        yield 'open details - any user sees breakdown' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_detailed_open']->getId() . '/detailed',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_detailed.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $table = $crawler->filter('table.results-breakdown');
                static::assertCount(1, $table);

                $rows = $table->filter('tbody tr');
                static::assertCount(1, $rows);

                $correctCells = $rows->eq(0)->filter('td.answer-correct');
                static::assertCount(4, $correctCells);

                $wrongCells = $rows->eq(0)->filter('td.answer-wrong');
                static::assertCount(2, $wrongCells);
            },
        ];

        yield 'not found for non-existent tournament' => [
            'method' => 'GET',
            'uri' => '/tournament/999999/detailed',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];
    }
}
