<?php

declare(strict_types=1);

namespace App\Tests\Controller\Venue;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
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
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

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
        yield 'list shows venues with towns and countries' => [
            'method' => 'GET',
            'uri' => '/venues/list',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(2, $rows);

                // sorted by name ASC
                $names = $rows->each(fn(Crawler $row) => $row->filter('td')->eq(0)->text() |> trim(...));
                static::assertStringContainsString('Арт-простір Львів', $names[0]);
                static::assertStringContainsString('Квіз-бар Київ', $names[1]);

                // towns
                $towns = $rows->each(fn(Crawler $row) => $row->filter('td')->eq(1)->text() |> trim(...));
                static::assertContains('Київ', $towns);
                static::assertContains('Львів', $towns);

                // country
                $countries = $rows->each(fn(Crawler $row) => $row->filter('td')->eq(2)->text() |> trim(...));
                static::assertSame(['Україна', 'Україна'], $countries);
            },
        ];

        yield 'list filters by name' => [
            'method' => 'GET',
            'uri' => '/venues/list?name=%D0%90%D1%80%D1%82',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                static::assertStringContainsString('Арт-простір Львів', $rows->eq(0)->text());
            },
        ];

        yield 'list filters by representative name' => [
            'method' => 'GET',
            'uri' => '/venues/list?representative=%D0%A8%D0%B5%D0%B2%D1%87',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                static::assertStringContainsString('Квіз-бар Київ', $rows->eq(0)->text());
            },
        ];

        yield 'list filters by townId' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/venues/list?townId=' . $objects['town_kyiv']->getId(),
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                $rows = $crawler->filter('table tbody tr');
                static::assertCount(1, $rows);
                static::assertStringContainsString('Квіз-бар Київ', $rows->eq(0)->text());
            },
        ];

        yield 'list filters by countryId' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/venues/list?countryId=' . $objects['country_ukraine']->getId(),
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertCount(2, $crawler->filter('table tbody tr'));
            },
        ];

        yield 'list empty when no venues' => [
            'method' => 'GET',
            'uri' => '/venues/list',
            'fixtures' => [],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertCount(0, $crawler->filter('table tbody tr td a'));
            },
        ];

        yield 'POST not allowed' => [
            'method' => 'POST',
            'uri' => '/venues/list',
            'fixtures' => [],
            'expectedStatus' => 405,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];
    }
}
