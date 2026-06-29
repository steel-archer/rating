<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\Tournament;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class SessionShowControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testSessionShow(
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
        $crawler = $client->request('GET', $resolvedUri);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($crawler, $objects);
    }

    /**
     * @return iterable<string, array{uri: string|callable, fixtures: list<string>, loginAs: string|null, expectedStatus: int, afterCallback: callable}>
     */
    public static function dataProvider(): iterable
    {
        yield 'anonymous gets redirected' => [
            'uri' => '/tournament/1/sessions/1',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'not found for non-existent session' => [
            'uri' => '/tournament/1/sessions/999999',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'not found when tournament id does not match session' => [
            'uri' => static fn(array $objects) => '/tournament/999999/sessions/' . $objects['session_spring_kyiv']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'shows session page with results' => [
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_spring']->getId() . '/sessions/' . $objects['session_spring_kyiv']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                // Title contains tournament name and venue
                static::assertSelectorTextContains('h1', 'Весняний кубок');
                static::assertSelectorTextContains('h1', 'Квіз-бар Київ');

                // Info card contains venue link, town, date, representative
                $card = $crawler->filter('.card dl');
                static::assertStringContainsString('Квіз-бар Київ', $card->text());
                static::assertStringContainsString('Київ', $card->text());
                static::assertStringContainsString('(онлайн)', $card->text());
                static::assertStringContainsString('01.03.2025', $card->text());
                static::assertStringContainsString('Шевченко Тарас Григорович', $card->text());

                // Venue link present
                $venueLink = $crawler->filter('a[href*="/venue/"]');
                static::assertGreaterThan(0, $venueLink->count());

                // Simple results table (details are hidden for this tournament)
                $table = $crawler->filter('table');
                static::assertCount(1, $table);
                $rows = $table->filter('tbody tr');
                // 2 teams in Kyiv session: alpha(25) and gamma(20)
                static::assertCount(2, $rows);
            },
        ];

        yield 'shows both session place and tournament place' => [
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_spring']->getId() . '/sessions/' . $objects['session_spring_kyiv']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $table = $crawler->filter('table');
                $rows = $table->filter('tbody tr');

                // Alpha: score 25, session place 1 (of 2 in this session), tournament place 2 (of 3)
                $firstRow = $rows->eq(0);
                $firstRow->filter('td')->eq(0)->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('2', $x)); // #Σ tournament place
                $firstRow->filter('td')->eq(1)->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('1', $x)); // # session place

                // Gamma: score 20, session place 2, tournament place 3
                $secondRow = $rows->eq(1);
                $secondRow->filter('td')->eq(0)->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('3', $x)); // #Σ tournament place
                $secondRow->filter('td')->eq(1)->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('2', $x)); // # session place
            },
        ];

        yield 'hidden results - regular user sees notice' => [
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_hidden_results']->getId() . '/sessions/' . $objects['session_hidden_kyiv']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_hidden.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertCount(0, $crawler->filter('table'));
                static::assertSelectorExists('.card p');
            },
        ];

        yield 'hidden results - official sees results' => [
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_hidden_results']->getId() . '/sessions/' . $objects['session_hidden_kyiv']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_hidden.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(2, $rows);
            },
        ];

        yield 'details visible - shows breakdown table' => [
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_detailed_open']->getId() . '/sessions/' . $objects['session_detailed_open']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_detailed.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $table = $crawler->filter('table.results-breakdown');
                static::assertCount(1, $table);

                $rows = $table->filter('tbody tr');
                static::assertCount(1, $rows);

                // Check correct/wrong answer cells
                $correctCells = $rows->eq(0)->filter('td.answer-correct');
                static::assertCount(4, $correctCells);

                $wrongCells = $rows->eq(0)->filter('td.answer-wrong');
                static::assertCount(2, $wrongCells);

                // Tour total shows format score/questionsPerTour
                $tourTotals = $rows->eq(0)->filter('td.tour-total-col');
                static::assertStringContainsString('/3', $tourTotals->eq(0)->text());

                // Total shows format score/maxScore
                $totalCell = $rows->eq(0)->filter('td.total-col');
                $totalCell->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('4/6', $x));
            },
        ];

        yield 'details hidden - official sees breakdown' => [
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_detailed']->getId() . '/sessions/' . $objects['session_detailed']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_detailed.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $table = $crawler->filter('table.results-breakdown');
                static::assertCount(1, $table);

                $rows = $table->filter('tbody tr');
                static::assertCount(2, $rows);
            },
        ];

        yield 'details hidden - regular user sees simple table' => [
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_detailed']->getId() . '/sessions/' . $objects['session_detailed']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_detailed.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                // No breakdown table
                static::assertCount(0, $crawler->filter('table.results-breakdown'));
                // Simple table with results
                $table = $crawler->filter('table');
                static::assertCount(1, $table);
                $rows = $table->filter('tbody tr');
                static::assertCount(2, $rows);
            },
        ];

        yield 'draft tournament - regular user gets 404' => [
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_draft']->getId() . '/sessions/' . $objects['session_draft']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournament_draft.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'draft tournament - moderator can view' => [
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_draft']->getId() . '/sessions/' . $objects['session_draft']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournament_draft.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_moderator',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertSelectorTextContains('h1', 'Чернетка');
            },
        ];

        yield 'breadcrumbs contain correct links' => [
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_spring']->getId() . '/sessions/' . $objects['session_spring_kyiv']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $breadcrumbs = $crawler->filter('.breadcrumbs');
                $links = $breadcrumbs->filter('a');
                // Home, Tournaments, Tournament name, Sessions
                static::assertGreaterThanOrEqual(4, $links->count());
                static::assertStringContainsString('/tournament/' . $objects['tournament_spring']->getId(), $links->eq(2)->attr('href'));
                static::assertStringContainsString('/sessions', $links->eq(3)->attr('href'));
            },
        ];
    }
}
