<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\Tournament;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Classic\Service\TournamentResultService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ResultsControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testResults(
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
            'uri' => '/tournament/1/results',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'results sorted by score desc with calculated places' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_spring']->getId() . '/results',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                // 3 teams: beta(30), alpha(25), gamma(20)
                static::assertCount(3, $rows);

                // 1st place: Beta with one-time name, score 30
                $first = $rows->eq(0);
                $first->filter('td')->eq(0)->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('1', $x));
                $hintCell = $first->filter('td')->eq(1);
                static::assertStringContainsString('^', $hintCell->text());
                static::assertStringContainsString('Бета', $hintCell->filter('span')->attr('data-tooltip'));
                $teamCell = $first->filter('td')->eq(2);
                static::assertStringContainsString('Зоряні Леви', $teamCell->text());
                static::assertCount(1, $teamCell->filter('em'));
                static::assertStringContainsString('30', $first->filter('td')->eq(4)->text());

                // 2nd place: Alpha, score 25 (no one-time name)
                $second = $rows->eq(1);
                $second->filter('td')->eq(0)->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('2', $x));
                static::assertSame('', trim($second->filter('td')->eq(1)->text()));
                static::assertStringContainsString('Альфа', $second->filter('td')->eq(2)->text());
                static::assertCount(0, $second->filter('td')->eq(2)->filter('em'));
                static::assertStringContainsString('25', $second->filter('td')->eq(4)->text());

                // 3rd place: Gamma, score 20
                $third = $rows->eq(2);
                $third->filter('td')->eq(0)->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('3', $x));
                static::assertStringContainsString('Гамма', $third->filter('td')->eq(2)->text());
                static::assertStringContainsString('20', $third->filter('td')->eq(4)->text());

                // town displayed
                static::assertStringContainsString('Львів', $first->filter('td')->eq(3)->text());
                static::assertStringContainsString('Київ', $second->filter('td')->eq(3)->text());
            },
        ];

        yield 'results for tournament with single team' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_autumn']->getId() . '/results',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                $rows->eq(0)->filter('td')->eq(0)->text()
                    |> trim(...)
                    |> (static fn($x) => static::assertSame('1', $x));
                static::assertStringContainsString('Альфа', $rows->eq(0)->filter('td')->eq(2)->text());
                static::assertStringContainsString('40', $rows->eq(0)->filter('td')->eq(4)->text());
            },
        ];

        yield 'results empty for tournament without teams' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_empty']->getId() . '/results',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournament_empty.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(0, $rows);
            },
        ];

        yield 'not found for non-existent tournament' => [
            'method' => 'GET',
            'uri' => '/tournament/999999/results',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'service unavailable on throwable' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_spring']->getId() . '/results',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, $client) {
                $client->disableReboot();
                $stub = $test->createStub(TournamentResultService::class);
                $stub->method('getResults')->willThrowException(new RuntimeException('DB down'));
                static::getContainer()->set(TournamentResultService::class, $stub);
            },
        ];

        yield 'hidden results show notice for regular user' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_hidden_results']->getId() . '/results',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_hidden.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertCount(0, $crawler->filter('table'));
            },
        ];

        yield 'hidden results visible to organizer' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_hidden_results']->getId() . '/results',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_hidden.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(2, $rows);
            },
        ];
    }
}
