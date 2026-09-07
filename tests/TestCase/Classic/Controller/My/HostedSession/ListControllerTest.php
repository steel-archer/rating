<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\HostedSession;

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
        string $loginAs,
        int $expectedApprovedRows,
        bool $expectsEmptyState,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        $client->loginUser($objects[$loginAs]);
        $client->request('GET', '/my/hosted-sessions');

        static::assertResponseIsSuccessful();

        $crawler = $client->getCrawler();

        if ($expectsEmptyState) {
            static::assertCount(1, $crawler->filter('.empty-state'));
            static::assertCount(0, $crawler->filter('tbody tr'));

            return;
        }

        static::assertCount(0, $crawler->filter('.empty-state'));
        static::assertCount($expectedApprovedRows, $crawler->filter('tbody tr'));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        // session_approved: host = player_shevchenko (user_representative), claim approved.
        // session_pending / session_rejected also have host = shevchenko but are not approved.
        yield 'host sees only approved hosted sessions' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'expectedApprovedRows' => 1,
            'expectsEmptyState' => false,
        ];

        yield 'player without hosted sessions sees empty state' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_other',
            'expectedApprovedRows' => 0,
            'expectsEmptyState' => true,
        ];
    }
}
