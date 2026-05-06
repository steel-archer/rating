<?php

declare(strict_types=1);

namespace App\Tests\Controller\Venue;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Service\VenueService;
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
        yield 'show venue with representative and tournaments' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/venue/' . $objects['venue_kyiv']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertSelectorTextContains('h1', 'Квіз-бар Київ');

                // town in meta
                static::assertStringContainsString('Київ', $crawler->filter('.meta')->text());

                // representative
                static::assertStringContainsString('Шевченко', $crawler->filter('.card')->text());

                // turbo-frame for tournaments (calculated tournamentCount > 0)
                $frame = $crawler->filter('turbo-frame#venue-tournaments');
                static::assertCount(1, $frame);
                static::assertStringContainsString('/tournaments', $frame->attr('src'));
            },
        ];

        yield 'show venue without tournaments' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/venue/' . $objects['venue_lviv']->getId(),
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertSelectorTextContains('h1', 'Арт-простір Львів');
                static::assertStringContainsString('Львів', $crawler->filter('.meta')->text());

                // representative
                static::assertStringContainsString('Франко', $crawler->filter('.card')->text());

                // no turbo-frame for tournaments (tournamentCount = 0)
                static::assertCount(0, $crawler->filter('turbo-frame#venue-tournaments'));
            },
        ];

        yield 'not found for non-existent venue' => [
            'method' => 'GET',
            'uri' => '/venue/999999',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'not found for non-numeric id' => [
            'method' => 'GET',
            'uri' => '/venue/abc',
            'fixtures' => [],
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];
        yield 'service unavailable on throwable' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/venue/' . $objects['venue_kyiv']->getId(),
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 503,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, $client) {
                $client->disableReboot();
                $stub = $test->createStub(VenueService::class);
                $stub->method('get')->willThrowException(new RuntimeException('DB down'));
                static::getContainer()->set(VenueService::class, $stub);
            },
        ];
    }
}
