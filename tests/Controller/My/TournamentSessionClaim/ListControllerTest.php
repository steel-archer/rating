<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\TournamentSessionClaim;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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

        $client->request('GET', '/my/tournament-claims');

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'organizer sees pending claims' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_organizer',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                $rows = $crawler->filter('table tbody tr');
                static::assertGreaterThanOrEqual(1, $rows->count());
            },
        ];

        yield 'organizer sees approved claims for active tournament' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims_active.yaml'],
            'loginAs' => 'user_organizer_active',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                static::assertStringContainsString(
                    'Схвалені заявки',
                    $crawler->text(),
                );
                $tables = $crawler->filter('#tournament-approved- table, [id^="tournament-approved-"] table');
                static::assertGreaterThanOrEqual(1, $tables->count());
            },
        ];

        yield 'empty for non-organizer' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_other',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                static::assertStringContainsString(
                    'Немає заявок на розгляд',
                    $crawler->text(),
                );
            },
        ];
    }
}
