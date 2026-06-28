<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\SessionClaim;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CreateControllerTest extends WebTestCase
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
    public function testCreate(
        array $fixtures,
        ?string $loginAs,
        string|callable $uri,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $resolvedUri = is_callable($uri) ? $uri($objects) : $uri;
        $client->request('GET', $resolvedUri);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'shows form for representative' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'uri' => static fn(array $objects) => '/my/session-claims/create/' . $objects['tournament_session_test']->getId(),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                static::assertCount(1, $crawler->filter('#session-claim-form'));

                $dateInput = $crawler->filter('#claim-date');
                static::assertSame('2025-06-01', $dateInput->attr('min'));
                static::assertSame('2025-06-30', $dateInput->attr('max'));

                $hint = $crawler->filter('#session-claim-form .hint');
                static::assertCount(1, $hint);
                static::assertStringContainsString('01.06.2025', $hint->text());
                static::assertStringContainsString('30.06.2025', $hint->text());
            },
        ];

        yield 'access denied for non-representative' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_other',
            'uri' => static fn(array $objects) => '/my/session-claims/create/' . $objects['tournament_session_test']->getId(),
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'registration closed' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims_expired.yaml'],
            'loginAs' => 'user_representative_exp',
            'uri' => static fn(array $objects) => '/my/session-claims/create/' . $objects['tournament_expired']->getId(),
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'not found for non-existent tournament' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'uri' => '/my/session-claims/create/999999',
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];
    }
}
