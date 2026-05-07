<?php

declare(strict_types=1);

namespace App\Tests\Controller\Moderator\PlayerClaim;

use App\Entity\PlayerClaim;
use App\Enum\PlayerClaimStatus;
use App\Service\PlayerClaimService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PlayerClaimRejectControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testReject(
        array $fixtures,
        ?string $loginAs,
        callable $action,
        int $expectedStatus,
        callable $afterCallback,
        ?callable $mockSetup = null,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        if ($mockSetup !== null) {
            $mockSetup($this, $client);
        }

        $action($client, $objects);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        $claimFixtures = ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/player_claims.yaml'];

        yield 'moderator rejects claim' => [
            'fixtures' => $claimFixtures,
            'loginAs' => 'user_moderator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/player-claims/' . $objects['player_claim_pending']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (array $objects) {
                $claim = static::getContainer()->get('doctrine')->getRepository(PlayerClaim::class)
                    ->find($objects['player_claim_pending']->getId());
                static::assertSame(PlayerClaimStatus::Rejected, $claim->getStatus());
            },
        ];

        yield 'double reject returns 422' => [
            'fixtures' => $claimFixtures,
            'loginAs' => 'user_admin',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['player_claim_pending']->getId();
                $client->request('POST', "/moderator/player-claims/$id/reject", [], [], ['CONTENT_TYPE' => 'application/json']);
                $client->request('POST', "/moderator/player-claims/$id/reject", [], [], ['CONTENT_TYPE' => 'application/json']);
            },
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'regular user gets 403' => [
            'fixtures' => $claimFixtures,
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/player-claims/' . $objects['player_claim_pending']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'anonymous gets redirected' => [
            'fixtures' => $claimFixtures,
            'loginAs' => null,
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/player-claims/' . $objects['player_claim_pending']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'reject throwable returns 500' => [
            'fixtures' => $claimFixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/player-claims/' . $objects['player_claim_pending']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(PlayerClaimService::class);
                $stub->method('reject')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(PlayerClaimService::class, $stub);
            },
        ];
    }
}
