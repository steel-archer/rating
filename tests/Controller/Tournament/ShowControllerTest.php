<?php

declare(strict_types=1);

namespace App\Tests\Controller\Tournament;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Service\TournamentService;
use RuntimeException;
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
            'uri' => '/tournament/1',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'show tournament with calculated fields' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_spring']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertSelectorTextContains('h1', 'Весняний кубок');

                $card = $crawler->filter('.card dl');

                // toursCount
                static::assertStringContainsString('3', $card->text());
                // questionsPerTour
                static::assertStringContainsString('12', $card->text());
                // difficulty
                static::assertStringContainsString('3.5', $card->text());
                // trueDl
                static::assertStringContainsString('2.8', $card->text());

                // teamCount: 3 teams (alpha, beta, gamma) — calculated
                $ddTexts = $card->filter('dd')->each(fn(Crawler $dd) => $dd->text() |> trim(...));
                static::assertContains('3', $ddTexts, 'Expected teamCount=3 in card');

                // sessionCount: 2 sessions (Kyiv + Lviv) — calculated
                $sessionLink = $crawler->filter('a[href*="/sessions"]');
                static::assertStringContainsString('2', $sessionLink->text());

                // officials: Shevchenko as organizer, Franko as editor
                $officialsCard = $crawler->filter('.card-grow');
                static::assertStringContainsString('Шевченко', $officialsCard->text());
                static::assertStringContainsString('Франко', $officialsCard->text());

                // dates in meta
                static::assertStringContainsString('01.03.2025', $crawler->filter('.meta')->text());
                static::assertStringContainsString('15.03.2025', $crawler->filter('.meta')->text());
            },
        ];

        yield 'show tournament with 1 team and 1 session' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_autumn']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertSelectorTextContains('h1', 'Осінній бриз');
                static::assertStringContainsString('10.10.2024', $crawler->filter('.meta')->text());
                static::assertStringContainsString('4.2', $crawler->filter('.card dl')->text());
            },
        ];

        yield 'show tournament with no teams or sessions' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_empty']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournament_empty.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertSelectorTextContains('h1', 'Порожній турнір');
                // no turbo-frame for results (teamCount = 0)
                static::assertCount(0, $crawler->filter('turbo-frame#tournament-results'));
            },
        ];

        yield 'not found for non-existent tournament' => [
            'method' => 'GET',
            'uri' => '/tournament/999999',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'not found for non-numeric id' => [
            'method' => 'GET',
            'uri' => '/tournament/abc',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'service unavailable on throwable' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_spring']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, $client) {
                $client->disableReboot();
                $stub = $test->createStub(TournamentService::class);
                $stub->method('get')->willThrowException(new RuntimeException('DB down'));
                static::getContainer()->set(TournamentService::class, $stub);
            },
        ];
    }
}
