<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\PlayerClaim;

use App\Common\Entity\PlayerClaim;
use App\Common\Enum\PlayerClaimStatus;
use App\Common\Exception\PlayerClaimException;
use App\Common\Service\PlayerClaimService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
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
        yield 'user submits new player claim' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/player-claim/new',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'lastName' => 'Тестовий',
                    'firstName' => 'Гравець',
                    'patronymic' => 'Тестович',
                    'townId' => $objects['town_kyiv']->getId(),
                    'termsAccepted' => true,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 201,
            'afterCallback' => static function (array $objects) {
                $claims = static::getContainer()->get('doctrine')->getRepository(PlayerClaim::class)
                    ->findBy(['user' => $objects['user_regular']]);
                static::assertCount(1, $claims);
                static::assertSame('Тестовий', $claims[0]->getLastName());
                static::assertSame('Гравець', $claims[0]->getFirstName());
                static::assertSame(PlayerClaimStatus::Pending, $claims[0]->getStatus());
                static::assertNull($claims[0]->getPlayer());
                static::assertSame('Київ', $claims[0]->getTown()?->getName());
            },
        ];

        yield 'user submits new player claim with new town and existing country' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/player-claim/new',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'lastName' => 'Тестовий',
                    'firstName' => 'Гравець',
                    'townName' => 'Новомісто',
                    'countryId' => $objects['country_ukraine']->getId(),
                    'termsAccepted' => true,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 201,
            'afterCallback' => static function (array $objects) {
                $claims = static::getContainer()->get('doctrine')->getRepository(PlayerClaim::class)
                    ->findBy(['user' => $objects['user_regular']]);
                static::assertCount(1, $claims);
                static::assertNull($claims[0]->getTown());
                static::assertSame('Новомісто', $claims[0]->getTownName());
                static::assertSame('Україна', $claims[0]->getCountry()?->getName());
            },
        ];

        yield 'new town without country returns 422' => [
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
                    'townName' => 'Новомісто',
                    'termsAccepted' => true,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'new town with unknown countryId returns 422' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/player-claim/new',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'lastName' => 'Тестовий',
                    'firstName' => 'Гравець',
                    'townName' => 'Новомісто',
                    'countryId' => $objects['country_ukraine']->getId() + 100000,
                    'termsAccepted' => true,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (array $objects) {
                $claims = static::getContainer()->get('doctrine')->getRepository(PlayerClaim::class)
                    ->findBy(['user' => $objects['user_regular']]);
                static::assertCount(0, $claims);
            },
        ];

        yield 'missing town returns 422' => [
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
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
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

        yield 'PlayerClaimException returns 422 with message' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/player-claim/new',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'lastName' => 'Тестовий',
                    'firstName' => 'Гравець',
                    'townId' => $objects['town_kyiv']->getId(),
                    'termsAccepted' => true,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(PlayerClaimService::class);
                $stub->method('claimNew')->willThrowException(new PlayerClaimException('Заявку вже подано'));
                static::getContainer()->set(PlayerClaimService::class, $stub);
            },
        ];

        yield 'terms not accepted returns 422' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/player-claim/new',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'lastName' => 'Тестовий',
                    'firstName' => 'Гравець',
                    'townId' => $objects['town_kyiv']->getId(),
                    'termsAccepted' => false,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'throwable returns 500' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/player-claim/new',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'lastName' => 'Тестовий',
                    'firstName' => 'Гравець',
                    'townId' => $objects['town_kyiv']->getId(),
                    'termsAccepted' => true,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(PlayerClaimService::class);
                $stub->method('claimNew')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(PlayerClaimService::class, $stub);
            },
        ];
    }
}
