<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\SessionClaim\Results;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResultsControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/results.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testResults(
        array $fixtures,
        ?string $loginAs,
        callable $uri,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request('GET', $uri($objects));

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'results page for owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $crawler = $client->getCrawler();
                static::assertCount(1, $crawler->filter('#upload-btn'));
                static::assertCount(1, $crawler->filter('a[href*="template"]'));
                static::assertCount(0, $crawler->filter('#submit-btn'));
            },
        ];

        yield 'access denied for non-owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_other',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results',
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'redirect for anonymous' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results',
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'not found for non-existent session' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/999999/results',
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'shows submit button and breakdown when unsubmitted results exist' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/results_uploaded.yaml'],
            'loginAs' => 'user_results_uploaded_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results_uploaded']->getId() . '/results',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $crawler = $client->getCrawler();
                static::assertCount(1, $crawler->filter('#submit-btn'));
                static::assertCount(1, $crawler->filter('.results-breakdown'));
                // 2 teams in breakdown
                static::assertCount(2, $crawler->filter('.results-breakdown tbody tr'));
                // Per-question answer cells exist (2 tours × 3 questions = 6 answer-col per row)
                $answerCells = $crawler->filter('.results-breakdown tbody .answer-col');
                static::assertGreaterThan(0, $answerCells->count());
            },
        ];
    }
}
