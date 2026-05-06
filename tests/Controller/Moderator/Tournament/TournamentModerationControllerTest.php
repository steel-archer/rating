<?php

declare(strict_types=1);

namespace App\Tests\Controller\Moderator\Tournament;

use App\Entity\TournamentModerationClaim;
use App\Entity\TournamentModerationStatus;
use App\Tests\CsrfTrait;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TournamentModerationControllerTest extends WebTestCase
{
    use FixturesTrait;
    use CsrfTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testModeration(
        array $fixtures,
        ?string $loginAs,
        callable $action,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $action($client, $objects);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        $fixtures = [
            'Entity/base.yaml',
            'Entity/users.yaml',
            'Entity/tournament_moderation.yaml',
        ];

        yield 'list requires moderator' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/moderator/tournaments'),
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'list shows pending claims' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/moderator/tournaments'),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringContainsString('Турнір на модерації', $client->getCrawler()->text());
            },
        ];

        yield 'approve tournament' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['tournament_pending']->getId();
                $crawler = $client->request('GET', '/moderator/tournaments');
                $token = self::extractCsrfToken($crawler, "/moderator/tournaments/$id/approve");
                $client->request('POST', "/moderator/tournaments/$id/approve", ['_token' => $token]);
            },
            'expectedStatus' => 302,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentModerationClaim::class)
                    ->findOneBy(['tournament' => $objects['tournament_pending']->getId()]);
                static::assertSame(TournamentModerationStatus::Approved, $claim->getStatus());
            },
        ];

        yield 'reject tournament with comment' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['tournament_pending']->getId();
                $crawler = $client->request('GET', '/moderator/tournaments');
                $token = self::extractCsrfToken($crawler, "/moderator/tournaments/$id/reject");
                $client->request('POST', "/moderator/tournaments/$id/reject", [
                    '_token' => $token,
                    'comment' => 'Назва не підходить',
                ]);
            },
            'expectedStatus' => 302,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentModerationClaim::class)
                    ->findOneBy(['tournament' => $objects['tournament_pending']->getId()]);
                static::assertSame(TournamentModerationStatus::Rejected, $claim->getStatus());
                static::assertSame('Назва не підходить', $claim->getComment());
            },
        ];

        yield 'show page visible to moderator for unpublished' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request('GET', '/tournament/' . $objects['tournament_pending']->getId()),
            'expectedStatus' => 200,
            'afterCallback' => static function () {
            },
        ];

        yield 'show page hidden from regular user for unpublished' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request('GET', '/tournament/' . $objects['tournament_pending']->getId()),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'show page visible to owner for unpublished' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request('GET', '/tournament/' . $objects['tournament_pending']->getId()),
            'expectedStatus' => 200,
            'afterCallback' => static function () {
            },
        ];

        yield 'double approve redirects without error' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['tournament_pending']->getId();
                $crawler = $client->request('GET', '/moderator/tournaments');
                $token = self::extractCsrfToken($crawler, "/moderator/tournaments/$id/approve");
                $client->request('POST', "/moderator/tournaments/$id/approve", ['_token' => $token]);
                // Second moderator tries to approve the same
                $client->request('POST', "/moderator/tournaments/$id/approve", ['_token' => $token]);
            },
            'expectedStatus' => 302,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentModerationClaim::class)
                    ->findOneBy(['tournament' => $objects['tournament_pending']->getId()]);
                static::assertSame(TournamentModerationStatus::Approved, $claim->getStatus());
            },
        ];

        yield 'double reject redirects without error' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['tournament_pending']->getId();
                $crawler = $client->request('GET', '/moderator/tournaments');
                $token = self::extractCsrfToken($crawler, "/moderator/tournaments/$id/reject");
                $client->request('POST', "/moderator/tournaments/$id/reject", [
                    '_token' => $token,
                    'comment' => 'Перший реджект',
                ]);
                // Second moderator tries to reject the same
                $client->request('POST', "/moderator/tournaments/$id/reject", [
                    '_token' => $token,
                    'comment' => 'Другий реджект',
                ]);
            },
            'expectedStatus' => 302,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentModerationClaim::class)
                    ->findOneBy(['tournament' => $objects['tournament_pending']->getId()]);
                static::assertSame(TournamentModerationStatus::Rejected, $claim->getStatus());
                static::assertSame('Перший реджект', $claim->getComment());
            },
        ];
    }
}
