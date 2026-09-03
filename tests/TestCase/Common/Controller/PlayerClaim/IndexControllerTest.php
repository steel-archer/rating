<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\PlayerClaim;

use App\Common\Entity\User;
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
        ?callable $afterCallback = null,
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

        if ($afterCallback !== null) {
            $afterCallback($client);
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
            'afterCallback' => null,
        ];

        yield 'regular user sees claim page with license and privacy links' => [
            'uri' => '/player-claim',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 200,
            'expectedRedirect' => null,
            'afterCallback' => static function ($client) {
                static::assertSelectorExists('.checkbox-label a[href="/license"]');
                static::assertSelectorExists('.checkbox-label a[href="/privacy"]');
                static::assertSelectorTextContains('.checkbox-label', 'умови користування сервісом');
                static::assertSelectorTextContains('.checkbox-label', 'політикою конфіденційності');
            },
        ];

        yield 'user with player redirects to home' => [
            'uri' => '/player-claim',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 302,
            'expectedRedirect' => '/',
            'afterCallback' => null,
        ];

        yield 'admin redirects to home' => [
            'uri' => '/player-claim',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_admin',
            'expectedStatus' => 302,
            'expectedRedirect' => '/',
            'afterCallback' => null,
        ];

        yield 'user with pending claim redirects to submitted' => [
            'uri' => '/player-claim',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/player_claims.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 302,
            'expectedRedirect' => '/player-claim/submitted',
            'afterCallback' => null,
        ];
    }
}
