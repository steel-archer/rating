<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\Moderator\PlayerClaim;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class PlayerClaimListControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testList(
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

        $crawler = $client->request('GET', '/moderator/player-claims');

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($crawler, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        $claimFixtures = ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/player_claims.yaml'];

        yield 'moderator sees claims with country column' => [
            'fixtures' => $claimFixtures,
            'loginAs' => 'user_admin',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                // Header contains a country column.
                $headers = $crawler->filter('table thead th')->each(static fn(Crawler $th) => trim($th->text()));
                static::assertContains('Країна', $headers);

                // Every pending claim resolves its country (Ukraine) through town.
                $rows = $crawler->filter('table tbody tr');
                static::assertGreaterThanOrEqual(1, $rows->count());
                foreach ($rows as $row) {
                    static::assertStringContainsString('Україна', (new Crawler($row))->text());
                }
            },
        ];

        yield 'new claim shows country resolved from stored country relation' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/player_claims_new_town.yaml'],
            'loginAs' => 'user_admin',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                // player_claim_new_with_town_name is linked to Ukraine via its country relation.
                static::assertStringContainsString('Україна', $crawler->filter('table tbody')->text());
            },
        ];

        yield 'new claim shows foreign country from the directory' => [
            'fixtures' => [
                'Entity/base.yaml',
                'Entity/countries_baltic.yaml',
                'Entity/tournaments.yaml',
                'Entity/users.yaml',
                'Entity/player_claims_foreign_country.yaml',
            ],
            'loginAs' => 'user_admin',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                // player_claim_new_with_foreign_country is linked to Lithuania.
                static::assertStringContainsString('Литва', $crawler->filter('table tbody')->text());
            },
        ];

        yield 'regular user gets 403' => [
            'fixtures' => $claimFixtures,
            'loginAs' => 'user_regular',
            'expectedStatus' => 403,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];

        yield 'anonymous gets redirected' => [
            'fixtures' => $claimFixtures,
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
            },
        ];
    }
}
