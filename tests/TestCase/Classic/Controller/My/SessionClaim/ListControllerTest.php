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

                /*
                 * Every tournament here still has open registration and
                 * submission windows, so all five claims are "current" and the
                 * closed block is not rendered at all.
                 */
                static::assertStringContainsString('Поточні', $crawler->text());
                static::assertStringNotContainsString('Закриті', $crawler->text());

                // The derived work-state badges are rendered for every claim.
                static::assertCount(5, $crawler->filter('.badge[class*="badge-work-"]'));

                /*
                 * The separate moderation "status" column was merged into the
                 * single work-state column, so the moderation status badges are
                 * gone and the header has eight columns instead of nine.
                 */
                static::assertCount(0, $crawler->filter('.badge[class*="badge-moderation-"]'));
                static::assertCount(8, $crawler->filter('table thead th'));

                // The moderation comment survives under the work-state badge.
                static::assertStringContainsString('Дата не підходить', $crawler->filter('table tbody')->text());

                /*
                 * The approved session (played 2025-06-20, in the past) now needs
                 * results, and that badge must sort to the very first row.
                 */
                $firstStateBadge = $crawler->filter('table tbody tr')->first()->filter('.badge-work-results-needed');
                static::assertCount(1, $firstStateBadge);
                static::assertStringContainsString('Внести результати', $crawler->text());

                // The approved future session is only waiting for its game day.
                static::assertCount(1, $crawler->filter('.badge-work-awaiting-game'));
                static::assertStringContainsString('Очікує гри', $crawler->text());

                // Two pending claims await moderation; the rejected one can be resubmitted.
                static::assertCount(2, $crawler->filter('.badge-work-awaiting-moderation'));
                static::assertCount(1, $crawler->filter('.badge-work-rejected'));

                /*
                 * The submission deadline hint is shown only where results can
                 * be entered now (results needed), that is the approved past
                 * session. The deadline is a year away, so it is the calm band:
                 * no "soon"/"last day" highlighting.
                 */
                static::assertStringContainsString('Внести до', $crawler->text());
                static::assertCount(0, $crawler->filter('.deadline-soon'));
                static::assertCount(0, $crawler->filter('.deadline-last-day'));
            },
        ];

        yield 'highlights an approaching submission deadline' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims_deadline_soon.yaml'],
            'loginAs' => 'user_representative_soon',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();

                /*
                 * Two approved claims whose games are already played: one with a
                 * deadline two days out (soon), one due tomorrow (last day). Both
                 * need results, so both show the deadline hint with the matching
                 * urgency class, and the last-day claim adds the extra warning.
                 */
                static::assertCount(1, $crawler->filter('.deadline-soon'));
                static::assertCount(1, $crawler->filter('.deadline-last-day'));
                static::assertCount(1, $crawler->filter('.deadline-warning'));
                static::assertStringContainsString('Сьогодні останній день!', $crawler->text());
            },
        ];

        yield 'places a claim past its submission deadline into the closed block' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims_expired.yaml'],
            'loginAs' => 'user_representative_exp',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                $pageText = $crawler->text();

                /*
                 * claim_expired_approved belongs to a tournament whose submission
                 * window has already passed, so it is the only claim and it lands
                 * in the closed block, never among current work.
                 */
                static::assertStringContainsString('Закриті', $pageText);
                static::assertStringNotContainsString('Поточні', $pageText);

                static::assertCount(1, $crawler->filter('.badge-work-closed'));
                static::assertStringContainsString('Закрито', $pageText);

                // Only the closed table is rendered when there is no current work.
                static::assertCount(1, $crawler->filter('table tbody'));
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
