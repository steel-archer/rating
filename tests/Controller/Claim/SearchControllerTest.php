<?php

declare(strict_types=1);

namespace App\Tests\Controller\Claim;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class SearchControllerTest extends WebTestCase
{
    use FixturesTrait;

    #[DataProvider('dataProvider')]
    public function testSearch(
        string $uri,
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

        $crawler = $client->request('GET', $uri);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($crawler, $objects);
    }

    public static function dataProvider(): iterable
    {
        yield 'anonymous gets 401' => [
            'uri' => '/claim/search',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 401,
            'afterCallback' => static function (Crawler $crawler, array $objects) {},
        ];

        yield 'logged in user searches free players' => [
            'uri' => '/claim/search?lastName=%D0%A4%D1%80%D0%B0%D0%BD',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                // Franko is free (no user linked), Shevchenko is taken by user_with_player
                static::assertStringContainsString('Франко', $crawler->text());
            },
        ];

        yield 'search with firstName and patronymic filters' => [
            'uri' => '/claim/search?firstName=%D0%86%D0%B2%D0%B0%D0%BD&patronymic=%D0%AF%D0%BA%D0%BE%D0%B2',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertStringContainsString('Франко', $crawler->text());
            },
        ];

        yield 'search with no results' => [
            'uri' => '/claim/search?lastName=Nonexistent',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertCount(0, $crawler->filter('table tbody tr'));
            },
        ];
    }
}
