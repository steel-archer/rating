<?php

declare(strict_types=1);

namespace App\Tests\Controller\Player;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Repository\PlayerRepository;
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
        yield 'list shows players with towns and current team' => [
            'method' => 'GET',
            'uri' => '/players/list',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(3, $rows);

                // sorted by lastName ASC: Ukrainka, Franko, Shevchenko
                $names = $rows->each(fn(Crawler $row) => $row->filter('td')->eq(0)->text() |> trim(...));
                static::assertStringContainsString('Українка', $names[0]);
                static::assertStringContainsString('Франко', $names[1]);
                static::assertStringContainsString('Шевченко', $names[2]);

                // towns
                $towns = $rows->each(fn(Crawler $row) => $row->filter('td')->eq(1)->text() |> trim(...));
                static::assertContains('Київ', $towns);
                static::assertContains('Львів', $towns);

                // Shevchenko has team Alpha (captain in current season)
                static::assertStringContainsString('Альфа', $rows->eq(2)->filter('td')->eq(2)->text());
            },
        ];

        yield 'list filters by lastName' => [
            'method' => 'GET',
            'uri' => '/players/list?lastName=%D0%A4%D1%80%D0%B0%D0%BD',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                static::assertStringContainsString('Франко', $rows->eq(0)->text());
            },
        ];

        yield 'list filters by patronymic' => [
            'method' => 'GET',
            'uri' => '/players/list?patronymic=%D0%AF%D0%BA%D0%BE%D0%B2',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                static::assertStringContainsString('Франко', $rows->eq(0)->text());
            },
        ];

        yield 'list filters by townId' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/players/list?townId=' . $objects['town_kyiv']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(2, $rows);
            },
        ];

        yield 'list filters by countryId' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/players/list?countryId=' . $objects['country_ukraine']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertCount(3, $crawler->filter('table tbody tr'));
            },
        ];

        yield 'list empty when no players' => [
            'method' => 'GET',
            'uri' => '/players/list',
            'fixtures' => [],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertCount(0, $crawler->filter('table tbody tr td a'));
            },
        ];

        yield 'POST not allowed' => [
            'method' => 'POST',
            'uri' => '/players/list',
            'fixtures' => [],
            'expectedStatus' => 405,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];
        yield 'service unavailable on throwable' => [
            'method' => 'GET',
            'uri' => '/players/list',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, $client) {
                $client->disableReboot();
                $stub = $test->createStub(PlayerRepository::class);
                $stub->method('findForList')->willThrowException(new RuntimeException('DB down'));
                static::getContainer()->set(PlayerRepository::class, $stub);
            },
        ];
    }
}
