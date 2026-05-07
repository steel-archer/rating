<?php

declare(strict_types=1);

namespace App\Tests\Controller\PlayerClaim;

use App\Entity\PlayerClaim;
use App\Enum\PlayerClaimStatus;
use App\Service\PlayerClaimService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ClaimExistingControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testClaimExisting(
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
        yield 'user claims existing free player' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/player-claim/existing',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['playerId' => $objects['player_lesya']->getId()], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 201,
            'afterCallback' => static function (array $objects) {
                $claims = static::getContainer()->get('doctrine')->getRepository(PlayerClaim::class)
                    ->findBy(['user' => $objects['user_regular']]);
                static::assertCount(1, $claims);
                static::assertSame('Українка', $claims[0]->getLastName());
                static::assertNotNull($claims[0]->getPlayer());
                static::assertSame(PlayerClaimStatus::Pending, $claims[0]->getStatus());
            },
        ];

        yield 'claim non-existent player returns 404' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/player-claim/existing',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['playerId' => 999999], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'claim already taken player returns 404' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/player-claim/existing',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['playerId' => $objects['player_shevchenko']->getId()], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'user with player gets 422' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/player-claim/existing',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['playerId' => $objects['player_lesya']->getId()], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'anonymous gets redirected' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/player-claim/existing',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['playerId' => 1], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'throwable returns 500' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/player-claim/existing',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['playerId' => $objects['player_lesya']->getId()], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(PlayerClaimService::class);
                $stub->method('claimExisting')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(PlayerClaimService::class, $stub);
            },
        ];
    }
}
