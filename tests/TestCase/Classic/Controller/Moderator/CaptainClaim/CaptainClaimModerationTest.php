<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\Moderator\CaptainClaim;

use App\Classic\Entity\CaptainClaim;
use App\Classic\Entity\TeamPlayer;
use App\Classic\Entity\TeamPlayerTransfer;
use App\Classic\Enum\CaptainClaimStatus;
use App\Classic\Enum\TeamPlayerTransferType;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CaptainClaimModerationTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/captain_claims.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testModeration(
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
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'list page accessible for moderator' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_moderator',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/moderator/captain-claims'),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertSelectorTextContains('body', 'Хочу бути капітаном Бети');
            },
        ];

        yield 'list page shows history' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_moderator',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/moderator/captain-claims'),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertSelectorTextContains('body', 'Хочу бути капітаном Гамми');
                static::assertSelectorTextContains('body', 'Недостатньо досвіду');
            },
        ];

        yield 'list page denied for non-moderator' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_player',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/moderator/captain-claims'),
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'approve: player not in squad becomes captain' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_moderator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/captain-claims/' . $objects['claim_pending']->getId() . '/approve',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($json['success']);

                // Claim is approved
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(CaptainClaim::class)
                    ->find($objects['claim_pending']->getId());
                static::assertSame(CaptainClaimStatus::Approved, $claim->getStatus());
                static::assertNotNull($claim->getResolvedAt());

                // Player is now captain in team
                $teamPlayer = static::getContainer()->get('doctrine')
                    ->getRepository(TeamPlayer::class)
                    ->findOneBy([
                        'player' => $objects['player_kotsubynsky']->getId(),
                        'team' => $objects['team_beta']->getId(),
                        'isCaptain' => true,
                    ]);
                static::assertNotNull($teamPlayer);

                // Transfer record created
                $transfer = static::getContainer()->get('doctrine')
                    ->getRepository(TeamPlayerTransfer::class)
                    ->findOneBy([
                        'player' => $objects['player_kotsubynsky']->getId(),
                        'team' => $objects['team_beta']->getId(),
                        'type' => TeamPlayerTransferType::Joined,
                    ]);
                static::assertNotNull($transfer);
            },
        ];

        yield 'reject: with comment' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_moderator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/captain-claims/' . $objects['claim_pending']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['comment' => 'Причина відмови'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($json['success']);

                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(CaptainClaim::class)
                    ->find($objects['claim_pending']->getId());
                static::assertSame(CaptainClaimStatus::Rejected, $claim->getStatus());
                static::assertSame('Причина відмови', $claim->getModeratorComment());
                static::assertNotNull($claim->getResolvedAt());
            },
        ];

        yield 'reject: without comment fails' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_moderator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/captain-claims/' . $objects['claim_pending']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['comment' => ''], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'approve: already resolved fails' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_moderator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/captain-claims/' . $objects['claim_approved']->getId() . '/approve',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('captain_claim.error.already_resolved', $json['error']);
            },
        ];

        yield 'reject: already resolved fails' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_moderator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/captain-claims/' . $objects['claim_approved']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['comment' => 'Спроба відхилити'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('captain_claim.error.already_resolved', $json['error']);
            },
        ];

        yield 'approve: player already in squad becomes captain, old captain loses flag' => [
            'fixtures' => [
                'Entity/base.yaml',
                'Entity/captain_claims_in_squad.yaml',
            ],
            'loginAs' => 'user_cc_insquad_moderator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/captain-claims/' . $objects['claim_insquad_pending']->getId() . '/approve',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($json['success']);

                $em = static::getContainer()->get('doctrine')->getManager();
                $em->clear();

                // Claim is approved
                $claim = $em->getRepository(CaptainClaim::class)->find($objects['claim_insquad_pending']->getId());
                static::assertSame(CaptainClaimStatus::Approved, $claim->getStatus());

                // Franko is now captain
                $frankoEntry = $em->getRepository(TeamPlayer::class)->findOneBy([
                    'player' => $objects['player_franko']->getId(),
                    'season' => $objects['season_current']->getId(),
                ]);
                static::assertTrue($frankoEntry->isCaptain());

                // Shevchenko is no longer captain
                $shevchenkoEntry = $em->getRepository(TeamPlayer::class)->findOneBy([
                    'player' => $objects['player_shevchenko']->getId(),
                    'season' => $objects['season_current']->getId(),
                ]);
                static::assertFalse($shevchenkoEntry->isCaptain());
            },
        ];

        yield 'approve: denied for non-moderator' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_player',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/captain-claims/' . $objects['claim_pending']->getId() . '/approve',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'approve: team full fails' => [
            'fixtures' => [
                'Entity/base.yaml',
                'Entity/captain_claims_team_full.yaml',
            ],
            'loginAs' => 'user_cc_full_moderator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/captain-claims/' . $objects['claim_team_full']->getId() . '/approve',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('captain_claim.error.team_full', $json['error']);

                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(CaptainClaim::class)
                    ->find($objects['claim_team_full']->getId());
                static::assertSame(CaptainClaimStatus::Pending, $claim->getStatus());
            },
        ];

        yield 'approve: player moved to another team fails' => [
            'fixtures' => [
                'Entity/base.yaml',
                'Entity/captain_claims_player_moved.yaml',
            ],
            'loginAs' => 'user_cc_moved_moderator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/captain-claims/' . $objects['claim_player_moved']->getId() . '/approve',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('captain_claim.error.in_another_team', $json['error']);

                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(CaptainClaim::class)
                    ->find($objects['claim_player_moved']->getId());
                static::assertSame(CaptainClaimStatus::Pending, $claim->getStatus());
            },
        ];
    }
}
