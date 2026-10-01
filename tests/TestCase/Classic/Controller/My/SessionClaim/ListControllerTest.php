<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\SessionClaim;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ListControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/session_claims.yaml',
    ];

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

        $client->request('GET', '/my/session-claims');

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'shows claims for every venue the player represents' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                $rows = $crawler->filter('table tbody tr');

                /*
                 * Shevchenko represents venue_kyiv and venue_lviv. Claims are
                 * shared across a venue's representatives, so he sees all four
                 * of his own claims plus claim_no_user, submitted by another
                 * representative of venue_lviv.
                 */
                static::assertCount(5, $rows);

                // claim_no_user (0 / 6) was submitted by a different representative of venue_lviv.
                $tableText = $crawler->filter('table tbody')->text();
                static::assertStringContainsString('0 / 6', $tableText);

                // The approved session carries an announcement URL that must be rendered as a link.
                $announcementLinks = $crawler->filter('table tbody a[href="https://example.com/announcement"]');
                static::assertCount(1, $announcementLinks);

                // Teams column shows "actual / estimated"; no results submitted yet, so actual is 0.
                static::assertStringContainsString('0 / 8', $tableText);
                static::assertStringContainsString('0 / 10', $tableText);
            },
        ];

        yield 'co-representative sees shared claims of the venue' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_corep',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                $rows = $crawler->filter('table tbody tr');

                /*
                 * The co-representative submitted nothing personally, yet sees
                 * both venue_kyiv claims (pending and approved) submitted by
                 * Shevchenko, because claims are shared across representatives.
                 */
                static::assertCount(2, $rows);

                $tableText = $crawler->filter('table tbody')->text();
                static::assertStringContainsString('0 / 8', $tableText);
                static::assertStringContainsString('0 / 10', $tableText);
            },
        ];

        yield 'empty for user without claims' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_other',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                static::assertStringContainsString(
                    'У вас немає заявок на відіграші',
                    $crawler->text(),
                );
            },
        ];
    }
}
