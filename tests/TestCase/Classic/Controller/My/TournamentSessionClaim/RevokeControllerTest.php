<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\TournamentSessionClaim;

use App\Classic\Entity\Appeal;
use App\Classic\Entity\SessionClaim;
use App\Classic\Entity\TournamentSessionTeam;
use App\Classic\Entity\TournamentSessionTeamAnswer;
use App\Classic\Enum\SessionClaimStatus;
use App\Classic\Service\SessionClaimService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RevokeControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/session_claims.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testRevoke(
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
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'revoke by organizer' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_organizer',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournament-claims/' . $objects['session_approved']->getId() . '/revoke',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(SessionClaim::class)
                    ->findOneBy(['session' => $objects['session_approved']->getId()]);
                static::assertSame(SessionClaimStatus::Revoked, $claim->getStatus());
                static::assertNotNull($claim->getResolvedAt());
            },
        ];

        yield 'revoke by non-organizer' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournament-claims/' . $objects['session_approved']->getId() . '/revoke',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('common.error', $body['error']);
            },
        ];

        yield 'revoke pending claim' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_organizer',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournament-claims/' . $objects['session_pending']->getId() . '/revoke',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('session_claim.error.not_approved', $body['error']);
            },
        ];

        yield 'non-existent session' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_organizer',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/tournament-claims/999999/revoke',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'revoke with results and appeals deletes related data' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/session_claims_with_results.yaml'],
            'loginAs' => 'user_organizer',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournament-claims/' . $objects['session_approved_with_results']->getId() . '/revoke',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $em = static::getContainer()->get('doctrine')->getManager();

                $claim = $em->getRepository(SessionClaim::class)
                    ->findOneBy(['session' => $objects['session_approved_with_results']->getId()]);
                static::assertSame(SessionClaimStatus::Revoked, $claim->getStatus());

                $appeals = $em->getRepository(Appeal::class)
                    ->findBy(['tournamentSessionTeamAnswer' => $objects['answer_revoke_2']->getId()]);
                static::assertCount(0, $appeals);

                $answers = $em->getRepository(TournamentSessionTeamAnswer::class)
                    ->findBy(['tournamentSessionTeam' => $objects['session_team_revoke_alpha']->getId()]);
                static::assertCount(0, $answers);

                $teams = $em->getRepository(TournamentSessionTeam::class)
                    ->findBy(['tournamentSession' => $objects['session_approved_with_results']->getId()]);
                static::assertCount(0, $teams);
            },
        ];

        yield 'throwable returns 500' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_organizer',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournament-claims/' . $objects['session_approved']->getId() . '/revoke',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('common.error', $body['error']);
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(SessionClaimService::class);
                $stub->method('revoke')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(SessionClaimService::class, $stub);
            },
        ];
    }
}
