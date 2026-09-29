<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\SessionClaim;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EditControllerTest extends WebTestCase
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
    public function testEdit(
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
        yield 'edit page for owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_pending']->getId() . '/edit',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                static::assertCount(1, $crawler->filter('#session-claim-edit-form'));

                $dateInput = $crawler->filter('#edit-date');
                static::assertSame('2025-06-01', $dateInput->attr('min'));
                static::assertSame('2025-06-30', $dateInput->attr('max'));

                $hintsText = $crawler->filter('#session-claim-edit-form .hint')->text();
                static::assertStringContainsString('01.06.2025', $hintsText);
                static::assertStringContainsString('30.06.2025', $hintsText);
            },
        ];

        yield 'edit page shows rejection comment' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_rejected']->getId() . '/edit',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                static::assertStringContainsString('Дата не підходить', $crawler->filter('.rejection-notice')->text());
            },
        ];

        yield 'access denied for non-owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_other',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_pending']->getId() . '/edit',
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'not found for non-existent session' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'uri' => static fn(array $objects) => '/my/session-claims/999999/edit',
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'approved claim does not expose question package but notifies about host access' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_approved']->getId() . '/edit',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                static::assertCount(1, $crawler->filter('#session-claim-edit-form'));
                // The question package must not be reachable from the claim edit page anymore.
                static::assertCount(0, $crawler->filter('a.document-link'));
                // The representative is told the package is available to the host elsewhere.
                static::assertStringContainsString(
                    'Відіграші, які я веду',
                    $crawler->filter('.flash-info')->text(),
                );
                // The results entry section is clearly labelled so it is obvious where to enter results.
                $pageText = $crawler->text();
                static::assertStringContainsString('Результати відіграшу', $pageText);
                static::assertCount(1, $crawler->filter('.actions-card'));
                // The squads already entered are listed while the submission window is open,
                // so the representative can review and edit them.
                static::assertStringContainsString('Внесені склади', $pageText);
                static::assertStringContainsString('Бета', $pageText);
                // Edit and delete controls are available for each entered squad.
                static::assertCount(1, $crawler->filter('a[href*="/my/session-teams/"][href$="/edit"]'));
                static::assertCount(1, $crawler->filter('[data-squad-delete]'));
            },
        ];

        yield 'approved claim before play day shows explanation instead of results form' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_future']->getId() . '/edit',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                static::assertCount(1, $crawler->filter('#session-claim-edit-form'));
                // Results cannot be entered yet, so the action buttons must be absent.
                static::assertCount(0, $crawler->filter('.actions-card'));
                // Instead the representative is told that results become editable on the play day.
                static::assertStringContainsString('Внести результати можна буде', $crawler->text());
                static::assertStringContainsString('Результати відіграшу', $crawler->text());
            },
        ];

        yield 'approved claim after submission deadline warns results are closed' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims_expired.yaml'],
            'loginAs' => 'user_representative_exp',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_expired_approved']->getId() . '/edit',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                static::assertCount(1, $crawler->filter('#session-claim-edit-form'));
                // The submission window is closed, so no results action buttons are shown.
                static::assertCount(0, $crawler->filter('.actions-card'));
                // The representative is warned that the submission deadline has passed.
                static::assertStringContainsString('Термін подання результатів завершився', $crawler->filter('.flash-error')->text());
                static::assertStringContainsString('Результати відіграшу', $crawler->text());
                // The entered squads remain visible for reference, but as a read-only list:
                // no edit or delete controls are shown once the window is closed.
                static::assertStringContainsString('Бета', $crawler->text());
                static::assertCount(0, $crawler->filter('a[href*="/my/session-teams/"][href$="/edit"]'));
                static::assertCount(0, $crawler->filter('[data-squad-delete]'));
            },
        ];

        yield 'centralized tournament shows enter results buttons' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims_centralized.yaml'],
            'loginAs' => 'user_representative_cen',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_centralized_approved']->getId() . '/edit',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                $actions = $crawler->filter('.actions-card');
                static::assertCount(1, $actions);
                static::assertCount(2, $actions->filter('a.btn'));
            },
        ];
    }
}
