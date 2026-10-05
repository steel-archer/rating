<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\Tournament;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Classic\Service\TournamentService;
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
        yield 'anonymous gets redirected' => [
            'method' => 'GET',
            'uri' => '/tournament/1/sessions/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'sessions list with calculated team counts per session' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_spring']->getId() . '/sessions/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
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
                $dates = $rows->each(fn(Crawler $row) => trim($row->filter('td')->eq(3)->text()));
                static::assertContains('01.03.2025', $dates);

                // check representative names
                $reps = $rows->each(fn(Crawler $row) => trim($row->filter('td')->eq(4)->filter('a')->text()));
                static::assertContains('Шевченко Тарас Григорович', $reps);
                static::assertContains('Франко Іван Якович', $reps);

                // calculated team counts, shown as "actual / estimated": Kyiv session has 2 teams, Lviv has 1
                $teamCounts = $rows->each(fn(Crawler $row) => trim($row->filter('td')->eq(7)->text()));
                static::assertNotEmpty(array_filter($teamCounts, static fn(string $value): bool => str_starts_with($value, '2 /')));
                static::assertNotEmpty(array_filter($teamCounts, static fn(string $value): bool => str_starts_with($value, '1 /')));

                // online column: Kyiv session is online, Lviv is not
                $onlineValues = $rows->each(fn(Crawler $row) => trim($row->filter('td')->eq(2)->text()));
                static::assertContains('On', $onlineValues);
                static::assertContains('', $onlineValues);
            },
        ];

        yield 'unpublished tournament - regular player gets 404' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_unpublished']->getId() . '/sessions/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournament_unpublished_results.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertStringNotContainsString('Квіз-бар Київ', $crawler->html());
            },
        ];

        yield 'unpublished tournament - organizer sees sessions' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_unpublished']->getId() . '/sessions/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournament_unpublished_results.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                static::assertSame('Квіз-бар Київ', trim($rows->filter('td')->eq(0)->text()));
            },
        ];

        yield 'unpublished tournament - moderator sees sessions' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_unpublished']->getId() . '/sessions/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournament_unpublished_results.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_moderator',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                static::assertSame('Квіз-бар Київ', trim($rows->filter('td')->eq(0)->text()));
            },
        ];

        yield 'not found for non-existent tournament' => [
            'method' => 'GET',
            'uri' => '/tournament/999999/sessions/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'organizer sees former hosts struck through with download indicator' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_hosts']->getId() . '/sessions/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml', 'Entity/session_hosts.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                // Current host (Українка Леся) is shown normally.
                static::assertStringContainsString('Українка', $crawler->filter('.host-current')->text());

                // Former host (Франко) is shown struck through.
                $former = $crawler->filter('.host-former');
                static::assertCount(1, $former);
                static::assertStringContainsString('Франко', $former->text());

                // The former host downloaded the package, so the indicator is shown.
                static::assertGreaterThan(0, $crawler->filter('.host-downloaded')->count());
            },
        ];

        yield 'moderator sees former hosts too' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_hosts']->getId() . '/sessions/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml', 'Entity/session_hosts.yaml'],
            'loginAs' => 'user_moderator',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertCount(1, $crawler->filter('.host-former'));
                static::assertStringContainsString('Франко', $crawler->filter('.host-former')->text());
            },
        ];

        yield 'outsider player sees only the current host, no former hosts' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_hosts']->getId() . '/sessions/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml', 'Entity/session_hosts.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                // No extended host column for outsiders.
                static::assertCount(0, $crawler->filter('.host-former'));
                static::assertCount(0, $crawler->filter('.host-downloaded'));

                // The current host is still visible in the host column (6th column, index 5).
                $hostCell = $crawler->filter('table tbody tr')->eq(0)->filter('td')->eq(5);
                static::assertStringContainsString('Українка', $hostCell->text());
            },
        ];
    }
}
