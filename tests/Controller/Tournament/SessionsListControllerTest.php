<?php

declare(strict_types=1);

namespace App\Tests\Controller\Tournament;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Service\TournamentService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class SessionsListControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testSessionsList(
        string $method,
        string|callable $uri,
        array $fixtures,
        int $expectedStatus,
        callable $afterCallback,
        ?callable $mockSetup = null,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($mockSetup !== null) {
            $mockSetup($this, $client);
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
        yield 'sessions list with calculated team counts per session' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_spring']->getId() . '/sessions/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                // 2 sessions: Kyiv and Lviv
                static::assertCount(2, $rows);

                // check venues
                $venueNames = $rows->each(fn(Crawler $row) => trim($row->filter('td')->eq(0)->text()));
                static::assertContains('Квіз-бар Київ', $venueNames);
                static::assertContains('Арт-простір Львів', $venueNames);

                // check towns
                $townNames = $rows->each(fn(Crawler $row) => trim($row->filter('td')->eq(1)->text()));
                static::assertContains('Київ', $townNames);
                static::assertContains('Львів', $townNames);

                // check date
                $dates = $rows->each(fn(Crawler $row) => trim($row->filter('td')->eq(2)->text()));
                static::assertContains('01.03.2025', $dates);

                // check representative names
                $reps = $rows->each(fn(Crawler $row) => trim($row->filter('td')->eq(3)->text()));
                static::assertContains('Шевченко Тарас Григорович', $reps);
                static::assertContains('Франко Іван Якович', $reps);

                // calculated team counts: Kyiv session has 2 teams, Lviv has 1
                $teamCounts = $rows->each(fn(Crawler $row) => trim($row->filter('td')->eq(5)->text()));
                static::assertContains('2', $teamCounts);
                static::assertContains('1', $teamCounts);
            },
        ];

        yield 'not found for non-existent tournament' => [
            'method' => 'GET',
            'uri' => '/tournament/999999/sessions/list',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];
    }
}
