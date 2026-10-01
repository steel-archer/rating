<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Twig;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * End-to-end coverage of the navigation menu action-count badges, rendered by
 * the moderation_counts()/my_action_counts() Twig functions on every page.
 */
class MenuCounterTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = ['Entity/base.yaml', 'Entity/menu_counts.yaml'];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testMenuBadges(
        array $fixtures,
        ?string $loginAs,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $crawler = $client->request('GET', '/');

        static::assertResponseIsSuccessful();
        $afterCallback($crawler);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'moderator sees shared moderation badges' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_menu_mod',
            'afterCallback' => static function (Crawler $crawler): void {
                $toggle = self::moderationToggle($crawler);

                // Shared total: 2 player claims + 1 tournament + 1 captain claim + 1 venue.
                static::assertSame('5', self::toggleBadge($toggle));

                // Accessible label and tooltip accompany the badge.
                $badge = $toggle->filter('.dropdown-toggle > .menu-badge');
                static::assertSame('Нерозглянутих дій: 5', $badge->attr('aria-label'));
                static::assertSame('Нерозглянутих дій: 5', $badge->attr('title'));

                $items = self::subItemBadges($toggle);
                static::assertSame('2', $items['Заявки гравців'] ?? null);
                static::assertSame('1', $items['Турніри'] ?? null);
                static::assertSame('1', $items['Заявки на капітанство'] ?? null);
                static::assertSame('1', $items['Майданчики'] ?? null);
            },
        ];

        yield 'moderator has no personal badge (no player)' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_menu_mod',
            'afterCallback' => static function (Crawler $crawler): void {
                // A moderator without a linked player has no personal dropdown.
                static::assertCount(1, $crawler->filter('.dropdown'));
            },
        ];

        yield 'organizer and jury sees personal badges with jury counts' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_menu_franko',
            'afterCallback' => static function (Crawler $crawler): void {
                $toggle = self::personalToggle($crawler);

                // 3 organizer claims (2 shared + 1 solo) + 1 dispute + 1 appeal.
                static::assertSame('5', self::toggleBadge($toggle));

                $items = self::subItemBadges($toggle);
                static::assertSame('3', $items['Заявки на мої турніри'] ?? null);
                static::assertSame('1', $items['Спірні відповіді'] ?? null);
                static::assertSame('1', $items['Апеляції'] ?? null);
            },
        ];

        yield 'co-organizer sees only their own organizer claims' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_menu_lesya',
            'afterCallback' => static function (Crawler $crawler): void {
                $toggle = self::personalToggle($crawler);

                // lesya co-organizes only the shared tournament: 2 pending claims, no jury roles.
                static::assertSame('2', self::toggleBadge($toggle));

                $items = self::subItemBadges($toggle);
                static::assertSame('2', $items['Заявки на мої турніри'] ?? null);
                static::assertArrayNotHasKey('Спірні відповіді', $items);
                static::assertArrayNotHasKey('Апеляції', $items);
            },
        ];

        yield 'player without roles sees no personal badge' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_menu_plain',
            'afterCallback' => static function (Crawler $crawler): void {
                $toggle = self::personalToggle($crawler);

                static::assertNull(self::toggleBadge($toggle));
                static::assertSame([], self::subItemBadges($toggle));
            },
        ];

        yield 'anonymous visitor sees no badges at all' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'afterCallback' => static function (Crawler $crawler): void {
                static::assertCount(0, $crawler->filter('.menu-badge'));
            },
        ];
    }

    private static function moderationToggle(Crawler $crawler): Crawler
    {
        return self::dropdownContaining($crawler, 'Модерація');
    }

    private static function personalToggle(Crawler $crawler): Crawler
    {
        // The personal dropdown toggle shows the player's name.
        return self::dropdownContaining($crawler, 'Франко', 'Українка', 'Коцюбинський');
    }

    private static function dropdownContaining(Crawler $crawler, string ...$needles): Crawler
    {
        foreach ($crawler->filter('.dropdown') as $node) {
            $dropdown = new Crawler($node);
            $toggleText = $dropdown->filter('.dropdown-toggle')->text();
            foreach ($needles as $needle) {
                if (str_contains($toggleText, $needle)) {
                    return $dropdown;
                }
            }
        }

        static::fail('Dropdown not found for: ' . implode(', ', $needles));
    }

    /**
     * Badges are always present in the DOM; a zero badge carries the hidden
     * attribute, so a visible badge is one without it.
     */
    private static function toggleBadge(Crawler $dropdown): ?string
    {
        $badge = $dropdown->filter('.dropdown-toggle > .menu-badge');

        if ($badge->count() === 0 || $badge->attr('hidden') !== null) {
            return null;
        }

        return trim($badge->text());
    }

    /**
     * Maps each sub-item label to its visible badge value (hidden/zero badges excluded).
     *
     * @return array<string, string>
     */
    private static function subItemBadges(Crawler $dropdown): array
    {
        $result = [];
        foreach ($dropdown->filter('.dropdown-menu a') as $node) {
            $link = new Crawler($node);
            $badge = $link->filter('.menu-badge');
            if ($badge->count() === 0 || $badge->attr('hidden') !== null) {
                continue;
            }
            $label = trim($link->filter('span')->first()->text());
            $result[$label] = trim($badge->text());
        }

        return $result;
    }
}
