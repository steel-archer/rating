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
