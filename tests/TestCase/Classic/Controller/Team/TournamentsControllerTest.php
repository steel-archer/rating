<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\Team;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Classic\Service\TeamTournamentService;
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
            'uri' => '/team/1/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'team tournaments with calculated places and squad' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/team/' . $objects['team_alpha']->getId() . '/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                // Alpha plays in spring (score 25/36) and autumn (score 40/60)
                static::assertCount(2, $rows);

                $allText = $crawler->filter('table tbody')->text();

                // tournament names
                static::assertStringContainsString('Весняний кубок', $allText);
                static::assertStringContainsString('Осінній бриз', $allText);

                // scores with maxScore
                static::assertStringContainsString('25/36', $allText);
                static::assertStringContainsString('40/60', $allText);

                // squad players displayed
                static::assertStringContainsString('Шевченко', $allText);
            },
        ];

        yield 'team with no tournament appearances returns empty table' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/team/' . $objects['team_gamma']->getId() . '/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(0, $rows);
            },
        ];

        yield 'not found for non-existent team' => [
            'method' => 'GET',
            'uri' => '/team/999999/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'service unavailable on throwable' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/team/' . $objects['team_alpha']->getId() . '/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, $client) {
                $client->disableReboot();
                $stub = $test->createStub(TeamTournamentService::class);
                $stub->method('getTournaments')->willThrowException(new RuntimeException('DB down'));
                static::getContainer()->set(TeamTournamentService::class, $stub);
            },
        ];

        yield 'hidden results show empty score and place' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/team/' . $objects['team_alpha']->getId() . '/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments_hidden.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                $row = $rows->eq(0);
                // place column (index 3) and score column (index 4) should be empty
                static::assertSame('—', trim($row->filter('td')->eq(3)->text()));
                static::assertSame('—', trim($row->filter('td')->eq(4)->text()));
            },
        ];
    }
}
