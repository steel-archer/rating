<?php

declare(strict_types=1);

namespace App\Tests\Controller\Tournament;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Service\TournamentService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class SessionsControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testSessions(
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
        yield 'sessions page renders tournament name' => [
            'method' => 'GET',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_spring']->getId() . '/sessions',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertSelectorTextContains('h1', 'Весняний кубок');
                // turbo-frame with src to sessions list
                $frame = $crawler->filter('turbo-frame#tournament-sessions');
                static::assertCount(1, $frame);
                static::assertStringContainsString('/sessions/list', $frame->attr('src'));
            },
        ];

        yield 'not found for non-existent tournament' => [
            'method' => 'GET',
            'uri' => '/tournament/999999/sessions',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 404,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];
    }
}
