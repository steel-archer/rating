<?php

declare(strict_types=1);

namespace App\Tests\Controller\Team;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class TournamentsControllerTest extends WebTestCase
{
    use FixturesTrait;

    #[DataProvider('dataProvider')]
    public function testTournaments(
        string $method,
        string|callable $uri,
        array $fixtures,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        $resolvedUri = is_callable($uri) ? $uri($objects) : $uri;
        $crawler = $client->request($method, $resolvedUri);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($crawler, $objects);
    }

    public static function dataProvider(): iterable
    {
        yield 'team tournaments with calculated places and squad' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/team/' . $objects['team_alpha']->getId() . '/tournaments',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                // Альфа plays in spring (score 25) and autumn (score 40)
                static::assertCount(2, $rows);

                $allText = $crawler->filter('table tbody')->text();

                // tournament names
                static::assertStringContainsString('Весняний кубок', $allText);
                static::assertStringContainsString('Осінній бриз', $allText);

                // scores
                static::assertStringContainsString('25', $allText);
                static::assertStringContainsString('40', $allText);

                // squad players displayed
                static::assertStringContainsString('Шевченко', $allText);
            },
        ];

        yield 'team with no tournament appearances returns empty table' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/team/' . $objects['team_gamma']->getId() . '/tournaments',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(0, $rows);
            },
        ];

        yield 'not found for non-existent team' => [
            'method' => 'GET',
            'uri' => '/team/999999/tournaments',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {},
        ];
    }
}
