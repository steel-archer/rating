<?php

declare(strict_types=1);

namespace App\Tests\Controller\PlayerClaim;

use App\Entity\PlayerClaim;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ClaimNewControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testClaimNew(
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
        $afterCallback($objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'user submits new player claim' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/player-claim/new',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'lastName' => 'Тестовий',
                    'firstName' => 'Гравець',
                    'patronymic' => 'Тестович',
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 201,
            'afterCallback' => static function (array $objects) {
                $claims = static::getContainer()->get('doctrine')->getRepository(PlayerClaim::class)
                    ->findBy(['user' => $objects['user_regular']]);
                static::assertCount(1, $claims);
                static::assertSame('Тестовий', $claims[0]->getLastName());
                static::assertSame('Гравець', $claims[0]->getFirstName());
                static::assertSame(PlayerClaim::STATUS_PENDING, $claims[0]->getStatus());
                static::assertNull($claims[0]->getPlayer());
            },
        ];

        yield 'user with player gets 422' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/player-claim/new',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['lastName' => 'Test'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'user with pending claim gets 422' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/player_claims.yaml'],
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/player-claim/new',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['lastName' => 'Test'], JSON_THROW_ON_ERROR),
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
                '/player-claim/new',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['lastName' => 'Test'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];
    }
}
