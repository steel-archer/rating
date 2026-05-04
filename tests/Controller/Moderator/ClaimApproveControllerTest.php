<?php

declare(strict_types=1);

namespace App\Tests\Controller\Moderator;

use App\Entity\PlayerClaim;
use App\Tests\CsrfTrait;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ClaimApproveControllerTest extends WebTestCase
{
    use FixturesTrait;
    use CsrfTrait;

    public function testApproveAlreadyProcessedClaimShowsFlash(): void
    {
        $client = static::createClient();
        $objects = self::loadFixtures(['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/claims.yaml']);
        $client->loginUser($objects['user_admin']);

        $claimId = $objects['claim_pending']->getId();
        $approveUrl = '/moderator/claims/' . $claimId . '/approve';

        // get token from claims page
        $crawler = $client->request('GET', '/moderator/claims');
        $token = self::extractCsrfToken($crawler, $approveUrl);

        // first approve
        $client->request('POST', $approveUrl, ['_token' => $token]);
        static::assertResponseRedirects('/moderator/claims');

        // second approve with same token — claim already processed
        $client->request('POST', $approveUrl, ['_token' => $token]);
        static::assertResponseRedirects('/moderator/claims');
    }

    public function testApproveClaimForUserWhoAlreadyHasPlayerShowsFlash(): void
    {
        $client = static::createClient();
        $objects = self::loadFixtures([
            'Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/claims_user_has_player.yaml',
        ]);
        $client->loginUser($objects['user_admin']);

        $claimId = $objects['claim_user_has_player']->getId();
        $approveUrl = '/moderator/claims/' . $claimId . '/approve';

        $crawler = $client->request('GET', '/moderator/claims');
        $token = self::extractCsrfToken($crawler, $approveUrl);
        $client->request('POST', $approveUrl, ['_token' => $token]);

        // should redirect with error flash (user already has a player)
        static::assertResponseRedirects('/moderator/claims');
    }

    public function testApproveClaimForAlreadyTakenPlayerShowsFlash(): void
    {
        $client = static::createClient();
        $objects = self::loadFixtures(['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/claims_conflict.yaml']);
        $client->loginUser($objects['user_admin']);

        $claimId = $objects['claim_already_taken']->getId();
        $approveUrl = '/moderator/claims/' . $claimId . '/approve';

        $crawler = $client->request('GET', '/moderator/claims');
        $token = self::extractCsrfToken($crawler, $approveUrl);
        $client->request('POST', $approveUrl, ['_token' => $token]);

        // should redirect with error flash (player already claimed by another user)
        static::assertResponseRedirects('/moderator/claims');
    }

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testApprove(
        array $fixtures,
        ?string $loginAs,
        string $claimKey,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $claimId = $objects[$claimKey]->getId();
        $crawler = $client->request('GET', '/moderator/claims');

        if ($client->getResponse()->isSuccessful()) {
            $approveUrl = '/moderator/claims/' . $claimId . '/approve';
            $token = self::extractCsrfToken($crawler, $approveUrl);
            $client->request('POST', $approveUrl, ['_token' => $token]);
        }

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($objects, $claimKey);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'moderator approves existing player claim' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/claims.yaml'],
            'loginAs' => 'user_admin',
            'claimKey' => 'claim_pending',
            'expectedStatus' => 302,
            'afterCallback' => static function (array $objects, string $claimKey) {
                $claim = static::getContainer()->get('doctrine')->getRepository(PlayerClaim::class)
                    ->find($objects[$claimKey]->getId());
                static::assertSame(PlayerClaim::STATUS_APPROVED, $claim->getStatus());
            },
        ];

        yield 'moderator approves new player claim and player is created' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/claims.yaml'],
            'loginAs' => 'user_admin',
            'claimKey' => 'claim_new_pending',
            'expectedStatus' => 302,
            'afterCallback' => static function (array $objects, string $claimKey) {
                $claim = static::getContainer()->get('doctrine')->getRepository(PlayerClaim::class)
                    ->find($objects[$claimKey]->getId());
                static::assertSame(PlayerClaim::STATUS_APPROVED, $claim->getStatus());
                // new player should be created and linked to user
                $user = $claim->getUser();
                static::assertNotNull($user->getPlayer());
                static::assertSame('Новий', $user->getPlayer()->getLastName());
            },
        ];

        yield 'regular user gets 403' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/claims.yaml'],
            'loginAs' => 'user_regular',
            'claimKey' => 'claim_pending',
            'expectedStatus' => 403,
            'afterCallback' => static function (array $objects, string $claimKey) {
            },
        ];

        yield 'anonymous gets redirected' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/claims.yaml'],
            'loginAs' => null,
            'claimKey' => 'claim_pending',
            'expectedStatus' => 302,
            'afterCallback' => static function (array $objects, string $claimKey) {
            },
        ];
    }
}
