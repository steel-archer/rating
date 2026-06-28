<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\Player;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Classic\Service\PlayerTournamentService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class TournamentsControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testTournaments(
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
            'uri' => '/player/1/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'player tournaments with calculated places and legionary flag' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_shevchenko']->getId() . '/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                // Shevchenko plays in: spring(Alpha), spring(Gamma as legionary), autumn(Alpha)
                static::assertGreaterThanOrEqual(2, $rows->count());

                // check tournament names present
                $texts = $rows->each(fn(Crawler $row) => $row->text());
                $allText = implode(' ', $texts);
                static::assertStringContainsString('Весняний кубок', $allText);

                // legionary marker present (Gamma appearance)
                static::assertStringContainsString('не в складі', $allText);

                // scores with maxScore
                static::assertStringContainsString('25/36', $allText);
                static::assertStringContainsString('20/36', $allText);

                // Alpha has no one-time name — displayed normally
                static::assertStringContainsString('Альфа', $allText);
            },
        ];

        yield 'player tournaments shows one-time name with tooltip' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_franko']->getId() . '/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertGreaterThanOrEqual(1, $rows->count());

                // Franko plays in Beta which has one-time name
                $hintCell = $rows->eq(0)->filter('td')->eq(2);
                static::assertStringContainsString('^', $hintCell->text());
                static::assertStringContainsString('Бета', $hintCell->filter('span')->attr('data-tooltip'));
                $teamCell = $rows->eq(0)->filter('td')->eq(3);
                static::assertStringContainsString('Зоряні Леви', $teamCell->text());
                static::assertCount(1, $teamCell->filter('em'));
            },
        ];

        yield 'player with no tournament appearances returns empty table' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_franko']->getId() . '/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(0, $rows);
            },
        ];

        yield 'not found for non-existent player' => [
            'method' => 'GET',
            'uri' => '/player/999999/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'service unavailable on throwable' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_shevchenko']->getId() . '/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, $client) {
                $client->disableReboot();
                $stub = $test->createStub(PlayerTournamentService::class);
                $stub->method('getTournaments')->willThrowException(new RuntimeException('DB down'));
                static::getContainer()->set(PlayerTournamentService::class, $stub);
            },
        ];

        yield 'hidden results show empty score and place' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/player/' . $objects['player_shevchenko']->getId() . '/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_hidden.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                $row = $rows->eq(0);
                // place column (index 5) and score column (index 6) should be empty
                static::assertSame('—', trim($row->filter('td')->eq(5)->text()));
                static::assertSame('—', trim($row->filter('td')->eq(6)->text()));
            },
        ];
    }
}
