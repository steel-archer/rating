<?php

declare(strict_types=1);

namespace App\Tests\Controller\Moderator\PlayerClaim;

use App\Entity\PlayerClaim;
use App\Tests\CsrfTrait;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PlayerClaimRejectControllerTest extends WebTestCase
{
    use FixturesTrait;
    use CsrfTrait;

    public function testRejectAlreadyProcessedClaimShowsFlash(): void
    {
        $client = static::createClient();
        $objects = self::loadFixtures(['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/player_claims.yaml']);
        $client->loginUser($objects['user_admin']);

        $claimId = $objects['player_claim_pending']->getId();
        $rejectUrl = '/moderator/player-claims/' . $claimId . '/reject';

        // get token from claims page
        $crawler = $client->request('GET', '/moderator/player-claims');
        $token = self::extractCsrfToken($crawler, $rejectUrl);

        // first reject
        $client->request('POST', $rejectUrl, ['_token' => $token]);
        static::assertResponseRedirects('/moderator/player-claims');

        // second reject with same token — claim already processed
        $client->request('POST', $rejectUrl, ['_token' => $token]);
        static::assertResponseRedirects('/moderator/player-claims');
    }

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testReject(
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

        $claimId = $objects['player_claim_pending']->getId();
        $crawler = $client->request('GET', '/moderator/player-claims');

        if ($client->getResponse()->isSuccessful()) {
            $token = self::extractCsrfToken($crawler, '/reject');
            $client->request('POST', '/moderator/player-claims/' . $claimId . '/reject', ['_token' => $token]);
        }

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'moderator rejects claim' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/player_claims.yaml'],
            'loginAs' => 'user_moderator',
            'expectedStatus' => 302,
            'afterCallback' => static function (array $objects) {
                $claim = static::getContainer()->get('doctrine')->getRepository(PlayerClaim::class)
                    ->find($objects['player_claim_pending']->getId());
                static::assertSame(PlayerClaim::STATUS_REJECTED, $claim->getStatus());
            },
        ];

        yield 'regular user gets 403' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/player_claims.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 403,
            'afterCallback' => static function (array $objects) {
            },
        ];

        yield 'anonymous gets redirected' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/player_claims.yaml'],
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function (array $objects) {
            },
        ];
    }
}
