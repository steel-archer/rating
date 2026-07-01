<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\Venue;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Classic\Service\TournamentService;
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
            'uri' => '/venue/1/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'venue tournaments with names and dates' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/venue/' . $objects['venue_kyiv']->getId() . '/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                // Kyiv venue hosts: spring session + autumn session = 2 tournaments
                static::assertCount(2, $rows);

                $allText = $crawler->filter('table tbody')->text();
                static::assertStringContainsString('Весняний кубок', $allText);
                static::assertStringContainsString('Осінній бриз', $allText);
                static::assertStringContainsString('01.03.2025', $allText);
                static::assertStringContainsString('10.10.2024', $allText);

                // Spring session in Kyiv has 2 teams (alpha + gamma), autumn has 1 (alpha)
                $firstRowCells = $rows->first()->filter('td');
                static::assertSame('2', trim($firstRowCells->eq(3)->text()));
                $secondRowCells = $rows->last()->filter('td');
                static::assertSame('1', trim($secondRowCells->eq(3)->text()));
            },
        ];

        yield 'not found for non-existent venue' => [
            'method' => 'GET',
            'uri' => '/venue/999999/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];
    }
}
