<?php

declare(strict_types=1);

namespace App\Tests\Controller\Team;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Repository\TeamRepository;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ListControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testList(
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
        yield 'list shows teams with towns and countries' => [
            'method' => 'GET',
            'uri' => '/teams/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(3, $rows);

                // default sort by name ASC
                $names = $rows->each(fn(Crawler $row) => $row->filter('td')->eq(0)->text() |> trim(...));
                static::assertSame('Альфа', $names[0]);
                static::assertSame('Бета', $names[1]);
                static::assertSame('Гамма', $names[2]);

                // towns
                $towns = $rows->each(fn(Crawler $row) => $row->filter('td')->eq(1)->text() |> trim(...));
                static::assertContains('Київ', $towns);
                static::assertContains('Львів', $towns);

                // country
                $countries = $rows->each(fn(Crawler $row) => $row->filter('td')->eq(2)->text() |> trim(...));
                static::assertSame(['Україна', 'Україна', 'Україна'], $countries);
            },
        ];

        yield 'list sorts by town' => [
            'method' => 'GET',
            'uri' => '/teams/list?sort=town&dir=asc',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $towns = $crawler->filter('table tbody tr')
                    ->each(fn(Crawler $row) => $row->filter('td')->eq(1)->text() |> trim(...));
                // Kyiv before Lviv alphabetically
                static::assertSame('Київ', $towns[0]);
            },
        ];

        yield 'list filters by name' => [
            'method' => 'GET',
            'uri' => '/teams/list?name=%D0%91%D0%B5%D1%82',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                static::assertStringContainsString('Бета', $rows->eq(0)->text());
                static::assertStringContainsString('Львів', $rows->eq(0)->text());
            },
        ];

        yield 'list filters by townId' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/teams/list?townId=' . $objects['town_lviv']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                static::assertStringContainsString('Бета', $rows->eq(0)->text());
            },
        ];

        yield 'list filters by countryId' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/teams/list?countryId=' . $objects['country_ukraine']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertCount(3, $crawler->filter('table tbody tr'));
            },
        ];

        yield 'list empty when no teams' => [
            'method' => 'GET',
            'uri' => '/teams/list',
            'fixtures' => [],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertCount(0, $crawler->filter('table tbody tr td a'));
            },
        ];

        yield 'POST not allowed' => [
            'method' => 'POST',
            'uri' => '/teams/list',
            'fixtures' => [],
            'expectedStatus' => 405,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];
        yield 'service unavailable on throwable' => [
            'method' => 'GET',
            'uri' => '/teams/list',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 503,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, $client) {
                $client->disableReboot();
                $stub = $test->createStub(TeamRepository::class);
                $stub->method('findForList')->willThrowException(new RuntimeException('DB down'));
                static::getContainer()->set(TeamRepository::class, $stub);
            },
        ];
    }
}
