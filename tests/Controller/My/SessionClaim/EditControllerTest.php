<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\SessionClaim;

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

        yield 'approved claim shows documents section' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_approved']->getId() . '/edit',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                static::assertCount(1, $crawler->filter('#session-claim-edit-form'));
            },
        ];
    }
}
