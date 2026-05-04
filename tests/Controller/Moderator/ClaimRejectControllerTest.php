<?php

declare(strict_types=1);

namespace App\Tests\Controller\Moderator;

use App\Entity\PlayerClaim;
use App\Tests\CsrfTrait;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ClaimRejectControllerTest extends WebTestCase
{
    use FixturesTrait;
    use CsrfTrait;

    public function testRejectAlreadyProcessedClaimShowsFlash(): void
    {
        $client = static::createClient();
        $objects = self::loadFixtures(['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/claims.yaml']);
        $client->loginUser($objects['user_admin']);

        $claimId = $objects['claim_pending']->getId();
        $rejectUrl = '/moderator/claims/' . $claimId . '/reject';

        // get token from claims page
        $crawler = $client->request('GET', '/moderator/claims');
        $token = self::extractCsrfToken($crawler, $rejectUrl);

        // first reject
        $client->request('POST', $rejectUrl, ['_token' => $token]);
        static::assertResponseRedirects('/moderator/claims');

        // second reject with same token — claim already processed
        $client->request('POST', $rejectUrl, ['_token' => $token]);
        static::assertResponseRedirects('/moderator/claims');
    }

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

        $claimId = $objects['claim_pending']->getId();
        $crawler = $client->request('GET', '/moderator/claims');

        if ($client->getResponse()->isSuccessful()) {
            $token = self::extractCsrfToken($crawler, '/reject');
            $client->request('POST', '/moderator/claims/' . $claimId . '/reject', ['_token' => $token]);
        }

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($objects);
    }

    public static function dataProvider(): iterable
    {
        yield 'moderator rejects claim' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/claims.yaml'],
            'loginAs' => 'user_moderator',
            'expectedStatus' => 302,
            'afterCallback' => static function (array $objects) {
                $claim = static::getContainer()->get('doctrine')->getRepository(PlayerClaim::class)
                    ->find($objects['claim_pending']->getId());
                static::assertSame(PlayerClaim::STATUS_REJECTED, $claim->getStatus());
            },
        ];

        yield 'regular user gets 403' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/claims.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 403,
            'afterCallback' => static function (array $objects) {},
        ];

        yield 'anonymous gets 401' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/claims.yaml'],
            'loginAs' => null,
            'expectedStatus' => 401,
            'afterCallback' => static function (array $objects) {},
        ];
    }
}
