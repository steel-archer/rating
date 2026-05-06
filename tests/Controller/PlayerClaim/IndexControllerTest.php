<?php

declare(strict_types=1);

namespace App\Tests\Controller\PlayerClaim;

use App\Entity\User;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IndexControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testIndex(
        string $uri,
        array $fixtures,
        ?string $loginAs,
        int $expectedStatus,
        ?string $expectedRedirect,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request('GET', $uri);

        static::assertResponseStatusCodeSame($expectedStatus);
        if ($expectedRedirect !== null) {
            static::assertResponseRedirects($expectedRedirect);
        }
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'anonymous gets redirected' => [
            'uri' => '/player-claim',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 302,
            'expectedRedirect' => null,
        ];

        yield 'regular user sees claim page' => [
            'uri' => '/player-claim',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 200,
            'expectedRedirect' => null,
        ];

        yield 'user with player redirects to home' => [
            'uri' => '/player-claim',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 302,
            'expectedRedirect' => '/',
        ];

        yield 'admin redirects to home' => [
            'uri' => '/player-claim',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_admin',
            'expectedStatus' => 302,
            'expectedRedirect' => '/',
        ];

        yield 'user with pending claim redirects to submitted' => [
            'uri' => '/player-claim',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/player_claims.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 302,
            'expectedRedirect' => '/player-claim/submitted',
        ];
    }
}
