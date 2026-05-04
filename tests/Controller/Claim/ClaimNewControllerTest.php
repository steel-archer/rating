<?php

declare(strict_types=1);

namespace App\Tests\Controller\Claim;

use App\Entity\PlayerClaim;
use App\Tests\CsrfTrait;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ClaimNewControllerTest extends WebTestCase
{
    use FixturesTrait;
    use CsrfTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testClaimNew(
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

        // open claim page to get CSRF token
        $crawler = $client->request('GET', '/claim');

        if ($client->getResponse()->isSuccessful()) {
            $token = self::extractCsrfToken($crawler, '/claim/new');
            $client->request('POST', '/claim/new', [
                '_token' => $token,
                'lastName' => 'Тестовий',
                'firstName' => 'Гравець',
                'patronymic' => 'Тестович',
            ]);
        }

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
            'expectedStatus' => 302,
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

        yield 'user with player redirects to home' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 302,
            'afterCallback' => static function (array $objects) {
            },
        ];

        yield 'user with pending claim redirects to home' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/claims.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 302,
            'afterCallback' => static function (array $objects) {
            },
        ];

        yield 'anonymous gets 401' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 401,
            'afterCallback' => static function (array $objects) {
            },
        ];
    }
}
